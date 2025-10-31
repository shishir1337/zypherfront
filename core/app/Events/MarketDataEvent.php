<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MarketDataEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public $marketData;

    public function __construct($marketData)
    {
        try {
            configBroadcasting();
        } catch (\Exception $e) {
            // Pusher configuration failed, but continue anyway
            \Log::warning('Pusher configuration failed: ' . $e->getMessage());
        }
        $this->marketData = $marketData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('market-data');
    }


    public function broadcastAs()
    {
        return "market-data";
    }
}
