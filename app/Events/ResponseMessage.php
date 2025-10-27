<?php

namespace App\Events;

use App\Models\InquiryResponse;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResponseMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $response;
    public $payload;
    /**
     * Create a new event instance.
     */
    public function __construct(InquiryResponse $inquiry_response)
    {

        $this->response = $inquiry_response->load('sender');
        $inquiry_response->sender_name = $inquiry_response->sender ? $inquiry_response->sender->name : 'Unknown';
        $inquiry_response->timestamp = $inquiry_response->created_at?->toIso8601String();

        // Use array payload to be safe
        $this->payload = $inquiry_response->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->response->inquiry_id == 14) {
            Log::warning("BLOCKED: Attempt to broadcast to inquiry 14", [
                'response_id' => $this->response->id,
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            return [];
        }

        return [
            new PrivateChannel('inquiry.' . $this->response->inquiry_id),
        ];
    }

    public function broadcastAs()
    {
        return 'response.created';
    }

    public function broadcastWith(): array
    {
        return ['response' => $this->payload];
    }
}
