<?php

namespace App\Notifications;

use App\Models\Property;
use App\Models\PropertyInvestmentMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewInvestmentOpportunity extends Notification
{
    use Queueable;

    public function __construct(
        public Property $property,
        public PropertyInvestmentMetric $metric
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Investment Opportunity: {$this->property->title}")
            ->line("A new property matching your criteria is available.")
            ->line("**Cap Rate:** {$this->metric->cap_rate}%")
            ->line("**Gross Yield:** {$this->metric->gross_yield}%")
            ->line("**Investment Score:** {$this->metric->investment_score}/100")
            ->line("**Price:** ₹" . number_format($this->property->price))
            ->action('View Property', url("/properties/{$this->property->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'investment_opportunity',
            'property_id' => $this->property->id,
            'title' => $this->property->title,
            'cap_rate' => $this->metric->cap_rate,
            'score' => $this->metric->investment_score,
            'url' => "/properties/{$this->property->id}",
        ];
    }
}