<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class NotifyWalletOwner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    
    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Folio $folio,
        private readonly Remittance $remittance)
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $owner = $this->folio->owner;

        if (! $owner) {
            // Log and bail if no owner found (should not happen due to FK constraint).
            \Log::error("Folio {$this->folio->id} has no owner. Cannot send notification.");
            return;
        }

        $isCredited = $this->remittance->destination_folio_id === $this->folio->id;

        // Send notification to the wallet owner
        $owner->notify(new WalletActivityNotification(
            folio: $this->folio, remittance: $this->remittance, isCredited: $isCredited
        ));

        Log::info('NotifyWalletOwner job completed', [
            'user_id' => $owner->id,
            'folio_id' => $this->folio->id,
            'remittance_id' => $this->remittance->id,
            'direction' => $isCredited ? 'credit' : 'debit',
        ]);
    }

        /**
        * Handle a job failure.
        */
        public function failed(\Throwable $exception): void
        {
            Log::error('NotifyWalletOwner job exhausted all retries', [
                'folio_id' => $this->folio->id,
                'remittance_id' => $this->remittance->id,
                'error' => $exception->getMessage(),
            ]);
        }
}
