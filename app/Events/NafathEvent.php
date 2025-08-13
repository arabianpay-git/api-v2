<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NafathEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

  public $data;
  protected $id;

  public function __construct($data,$id)
  {
      $this->data = $data;
      $this->id = $id;

  }

  public function broadcastOn()
  {
    // return new PrivateChannel('arabianpay-' . $this->id);
    return new Channel('arabianpay-' . $this->id);
  }

  public function broadcastAs()
  {
      return 'event-arabianpay';
  }

  public function broadcastWith()
    {
        return $this->data;
    }
}
