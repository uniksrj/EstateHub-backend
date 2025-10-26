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
    /**
     * Create a new event instance.
     */
    public function __construct(InquiryResponse $inquiry_response)
    {

        $this->response = $inquiry_response->load('sender');
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
}
