<?php

namespace App\Observers;

use App\Events\TaskUpdated;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        TaskUpdated::dispatch($task, 'created');
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        TaskUpdated::dispatch($task, 'updated');
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        TaskUpdated::dispatch($task, 'deleted');
    }
}
