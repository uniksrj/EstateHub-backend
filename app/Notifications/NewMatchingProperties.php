<?php

namespace App\Notifications;

use App\Models\PropertyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMatchingProperties extends Notification implements ShouldQueue
{
    use Queueable;

    public $properties;
    public $alert;

    public function __construct($properties, PropertyAlert $alert)
    {
        $this->properties = $properties;
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->properties->count() . ' New Properties Match Your Criteria!')
            ->markdown('emails.property-alerts', [
                'properties' => $this->properties,
                'alert' => $this->alert,
                'count' => $this->properties->count()
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'message' => $this->properties->count() . ' new properties match your criteria',
            'properties_count' => $this->properties->count(),
            'action_url' => '/buyer/alerts/' . $this->alert->id
        ];
    }
}