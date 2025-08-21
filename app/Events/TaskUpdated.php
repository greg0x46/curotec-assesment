<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Task $task, public string $action)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        foreach (array_unique([$this->task->owner_id, $this->task->assigned_to_id]) as $userId) {
            if ($userId) {
                $channels[] = new PrivateChannel("tasks.user.{$userId}");
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'TaskUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'    => $this->task->id,
            'title' => $this->task->title,
            'status'=> $this->task->status,
            'action' => $this->action,
        ];
    }
}
