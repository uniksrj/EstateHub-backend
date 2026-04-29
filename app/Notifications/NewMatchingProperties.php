<?php

namespace App\Notifications;

use App\Models\PropertyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
        Log::info('Building email for alert: ' . $this->alert->id);
        Log::info('Properties count: ' . $this->properties->count());
        Log::info('Properties data: ', [$this->properties->toArray()]);

        try {
            $mail = (new MailMessage)
                ->subject($this->properties->count() . ' New Properties Match Your Criteria!')
                ->markdown('emails.property-alerts', [
                    'properties' => $this->properties,
                    'alert' => $this->alert,
                    'count' => $this->properties->count()
                ]);

            Log::info('Email built successfully');
            return $mail;
        } catch (\Exception $e) {
            Log::error('Error building email: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }



    // If using Twilio instead:
    public function toTwilio($notifiable)
    {
        return (new TwilioSmsMessage())
            ->content($this->properties->count() . ' new properties match your criteria. Check your email for details.');
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
