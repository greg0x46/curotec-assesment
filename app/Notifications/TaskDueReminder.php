<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Due Reminder')
            ->line('The task "' . $this->task->title . '" is due on ' . $this->task->due_date->toFormattedDateString() . '.')
            ->action('View Tasks', route('tasks.index'))
            ->line('Please ensure it is completed on time.');
    }
}
