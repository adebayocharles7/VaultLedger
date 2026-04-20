<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reconcile-wallet-balances')]
#[Description('Command description')]
class ReconcileWalletBalances extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('VaultLedger Reconciliation starting...');
 
        $discrepancies = 0;
        $checked       = 0;
 
        Folio::withoutTrashed()
            ->chunkById(200, function ($folios) use (&$discrepancies, &$checked) {
                foreach ($folios as $folio) {
                    $checked++;
                    $ledgerBalance = $folio->computeLedgerBalance();
 
                    if ($ledgerBalance !== $folio->balance_minor) {
                        $discrepancies++;
 
                        $this->error(sprintf(
                            '[MISMATCH] Folio %s | Stored: %d | Ledger: %d | Drift: %d',
                            $folio->id,
                            $folio->balance_minor,
                            $ledgerBalance,
                            $folio->balance_minor - $ledgerBalance
                        ));
 
                        if (! $this->option('dry-run')) {
                            // Heal: trust the ledger over the stored balance.
                            $folio->update(['balance_minor' => $ledgerBalance]);
                            $this->line("  → Corrected to {$ledgerBalance}");
                        }
                    }
                }
            });
 
        $this->info("Checked: {$checked} folios. Discrepancies: {$discrepancies}.");
 
        return $discrepancies > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
