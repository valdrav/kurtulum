<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_date' => $this->task->due_date?->toIso8601String(),
            'reminder_at' => $this->task->reminder_at?->toIso8601String(),
            'message' => __('tasks.reminder_notification', ['title' => $this->task->title]),
            'url' => route('tasks.index'),
        ];
    }
}
