<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Command;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Hatırlatma zamanı gelen görevler için bildirim oluşturur';

    public function handle(): int
    {
        $tasks = Task::query()
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('assignee')
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $user = $task->assignee;
            if (! $user) {
                continue;
            }

            $already = $user->notifications()
                ->where('type', TaskReminderNotification::class)
                ->where('data->task_id', $task->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($already) {
                continue;
            }

            $user->notify(new TaskReminderNotification($task));
            $sent++;
        }

        $this->info($sent > 0 ? "{$sent} görev hatırlatması gönderildi." : 'Gönderilecek hatırlatma yok.');

        return self::SUCCESS;
    }
}
