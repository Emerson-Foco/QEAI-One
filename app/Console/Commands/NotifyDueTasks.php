<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Support\Notify;
use Illuminate\Console\Command;

class NotifyDueTasks extends Command
{
    protected $signature = 'tasks:notify-due';

    protected $description = 'Notifica tarefas abertas que vencem nas próximas 24 horas.';

    public function handle(): int
    {
        $tasks = Task::where('status', 'open')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->get();

        foreach ($tasks as $task) {
            Notify::taskDue($task);
        }

        $this->info($tasks->count() . ' tarefa(s) notificada(s).');

        return self::SUCCESS;
    }
}
