<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Enums\RemittanceStatus;
use App\Events\FolioPosted;
use App\Events\RemittanceFailed;
use App\Exceptions\FolioFrozenException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\StaleVersionException;
use App\Models\Folio;
use App\Models\LedgerEntry;
use App\Models\Remittance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PostingService
 *
 * Owns the core accounting logic. All balance mutations go through here.
 *
 * Concurrency strategy:
 *   - Pessimistic locking (`lockForUpdate`) prevents two concurrent reads
 *     from racing on the same Folio row.
 *   - Optimistic version check (`version`) acts as a final guard — if a
 *     version mismatch sneaks through, we abort with 409 rather than
 *     silently corrupting the balance.
 */
class PostingService
{
    /**
     * Fund a Folio from an external source (top-up).
     *
     * @throws FolioFrozenException
     */
    public function fund(Folio $folio, int $amountMinor, string $narration = 'Top-up'): Remittance
    {
        return DB::transaction(function () use ($folio, $amountMinor, $narration) {

            /** @var Folio $folio */
            $folio = Folio::lockForUpdate()->findOrFail($folio->id);

            $this->assertFolioOperational($folio);

            // A funding has no source folio — null represents an external credit.
            $remittance = Remittance::create([
                'source_folio_id'      => null,
                'destination_folio_id' => $folio->id,
                'amount_minor'         => $amountMinor,
                'currency'             => $folio->currency,
                'status'               => RemittanceStatus::PENDING,
                'description'          => $narration,
            ]);

            $this->postCredit($folio, $remittance, $narration);

            $remittance->update([
                'status'    => RemittanceStatus::POSTED,
                'posted_at' => now(),
            ]);

            event(new FolioPosted($folio, $remittance));

            return $remittance->fresh(['destinationFolio', 'ledgerEntries']);
        });
    }

    /**
     * Withdraw from a Folio to an external sink.
     *
     * @throws FolioFrozenException
     * @throws InsufficientFundsException
     */
    public function withdraw(Folio $folio, int $amountMinor, string $narration = 'Withdrawal'): Remittance
    {
        return DB::transaction(function () use ($folio, $amountMinor, $narration) {

            /** @var Folio $folio */
            $folio = Folio::lockForUpdate()->findOrFail($folio->id);

            $this->assertFolioOperational($folio);
            $this->assertSufficientFunds($folio, $amountMinor);

            $remittance = Remittance::create([
                'source_folio_id'      => $folio->id,
                'destination_folio_id' => null,
                'amount_minor'         => $amountMinor,
                'currency'             => $folio->currency,
                'status'               => RemittanceStatus::PENDING,
                'description'          => $narration,
            ]);

            $this->postDebit($folio, $remittance, $narration);

            $remittance->update([
                'status'    => RemittanceStatus::POSTED,
                'posted_at' => now(),
            ]);

            event(new FolioPosted($folio, $remittance));

            return $remittance->fresh(['sourceFolio', 'ledgerEntries']);
        });
    }

    /**
     * Transfer funds between two Folios (peer-to-peer).
     *
     * Both Folios are locked in consistent ID order to prevent deadlocks.
     *
     * @throws FolioFrozenException
     * @throws InsufficientFundsException
     * @throws StaleVersionException
     */
    public function transfer(
        Folio $source,
        Folio $destination,
        int $amountMinor,
        string $narration = 'Transfer'
    ): Remittance {
        if ($source->id === $destination->id) {
            throw new \InvalidArgumentException('Source and destination Folio cannot be the same.');
        }

        return DB::transaction(function () use ($source, $destination, $amountMinor, $narration) {

            // Lock in deterministic order to prevent deadlocks across concurrent transfers.
            [$first, $second] = $source->id < $destination->id
                ? [$source->id, $destination->id]
                : [$destination->id, $source->id];

            $locked = Folio::lockForUpdate()
                ->whereIn('id', [$first, $second])
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            $source      = $locked[$source->id];
            $destination = $locked[$destination->id];

            $this->assertFolioOperational($source);
            $this->assertFolioOperational($destination);
            $this->assertSufficientFunds($source, $amountMinor);

            $remittance = Remittance::create([
                'source_folio_id'      => $source->id,
                'destination_folio_id' => $destination->id,
                'amount_minor'         => $amountMinor,
                'currency'             => $source->currency,
                'status'               => RemittanceStatus::PENDING,
                'description'          => $narration,
            ]);

            try {
                $this->postDebit($source, $remittance, $narration);
                $this->postCredit($destination, $remittance, $narration);

                $remittance->update([
                    'status'    => RemittanceStatus::POSTED,
                    'posted_at' => now(),
                ]);

                event(new FolioPosted($source, $remittance));
                event(new FolioPosted($destination, $remittance));

            } catch (Throwable $e) {
                $remittance->update([
                    'status'        => RemittanceStatus::FAILED,
                    'failed_reason' => $e->getMessage(),
                ]);

                event(new RemittanceFailed($remittance, $e->getMessage()));

                Log::error('PostingService: remittance failed', [
                    'remittance_id' => $remittance->id,
                    'reason'        => $e->getMessage(),
                ]);

                throw $e;
            }

            return $remittance->fresh(['sourceFolio', 'destinationFolio', 'ledgerEntries']);
        });
    }

    /**
     * Reverse a previously POSTED remittance by posting a counter-remittance.
     */
    public function reverse(Remittance $remittance, string $reason = 'Reversal'): Remittance
    {
        if ($remittance->status !== RemittanceStatus::POSTED) {
            throw new \LogicException('Only POSTED remittances can be reversed.');
        }

        // Swap source and destination to undo the original flow.
        $counterRemittance = $this->transfer(
            source:      $remittance->destinationFolio,
            destination: $remittance->sourceFolio,
            amountMinor: $remittance->amount_minor,
            narration:   "REV: {$reason} (ref: {$remittance->id})"
        );

        $remittance->update(['status' => RemittanceStatus::REVERSED]);

        return $counterRemittance;
    }

    // ──────────────────────────────────────────────
    // Private posting primitives
    // ──────────────────────────────────────────────

    private function postDebit(Folio $folio, Remittance $remittance, string $narration): LedgerEntry
    {
        $newBalance = $folio->balance_minor - $remittance->amount_minor;

        $this->applyVersionedUpdate($folio, $newBalance);

        return LedgerEntry::create([
            'folio_id'              => $folio->id,
            'remittance_id'         => $remittance->id,
            'type'                  => LedgerEntryType::DEBIT,
            'amount_minor'          => $remittance->amount_minor,
            'running_balance_minor' => $newBalance,
            'narration'             => $narration,
        ]);
    }

    private function postCredit(Folio $folio, Remittance $remittance, string $narration): LedgerEntry
    {
        $newBalance = $folio->balance_minor + $remittance->amount_minor;

        $this->applyVersionedUpdate($folio, $newBalance);

        return LedgerEntry::create([
            'folio_id'              => $folio->id,
            'remittance_id'         => $remittance->id,
            'type'                  => LedgerEntryType::CREDIT,
            'amount_minor'          => $remittance->amount_minor,
            'running_balance_minor' => $newBalance,
            'narration'             => $narration,
        ]);
    }

    /**
     * Atomically update balance + increment version.
     * Throws if the version changed since we locked (stale write guard).
     */
    private function applyVersionedUpdate(Folio $folio, int $newBalance): void
    {
        $affected = DB::table('folios')
            ->where('id', $folio->id)
            ->where('version', $folio->version)
            ->update([
                'balance_minor' => $newBalance,
                'version'       => $folio->version + 1,
                'updated_at'    => now(),
            ]);

        if ($affected === 0) {
            throw new StaleVersionException(
                "Folio [{$folio->id}] was modified by a concurrent request. Please retry."
            );
        }

        $folio->balance_minor = $newBalance;
        $folio->version++;
    }

    // ──────────────────────────────────────────────
    // Guards
    // ──────────────────────────────────────────────

    private function assertFolioOperational(Folio $folio): void
    {
        if (! $folio->isOperational()) {
            throw new FolioFrozenException(
                "Folio [{$folio->id}] is {$folio->status->label()} and cannot process transactions."
            );
        }
    }

    private function assertSufficientFunds(Folio $folio, int $amountMinor): void
    {
        if ($folio->balance_minor < $amountMinor) {
            throw new InsufficientFundsException(
                "Insufficient funds. Available: {$folio->balance_minor}, Requested: {$amountMinor}."
            );
        }
    }
}