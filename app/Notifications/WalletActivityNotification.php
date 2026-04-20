<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Enums\LedgerEntryType;
use App\Models\Folio;
use App\Models\Remittance;

class WalletActivityNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Folio $folio,
        private readonly Remittance $remittance,
        private readonly bool $isCredited)
    {
        
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $direction = $this->isCredited ? 'credited' : 'debited';
        $sign = $this->isCredited ? '+' : '-';
        $amount = number_format($this->remittance->amount_minor / 100, 2);
        $balance = number_format($this->folio->fresh()->balance_minor / 100, 2);
        $currency = $this->folio->currency;
        return (new MailMessage)
            ->subject("Your wallet was {$direction}")
            ->greeting("Hello! {$notifiable->name},")
            ->line("Your wallet has been{$direction}.")
            ->line("**Amount:** {$sign}{$amount} {$currency}")
            ->line("**New Balance:** {$balance} {$currency}")
            ->line("**Reference:** {$this->remittance->id}")
            ->line($this->remittance->description ?? '')
            ->action('View Ledger', url("/folios/{$this->folio->id}/ledger"))
            ->line('If you did not authorise this transaction, contact support immediately.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'folio_id' => $this->folio->id,
            'remittance_id' => $this->remittance->id,
            'type' => $this->isCredited ? 'credit' : 'debit',
            'amount_minor' => $this->remittance->amount_minor,
            'currency' => $this->folio->currency,
            'description' => $this->remittance->description,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
