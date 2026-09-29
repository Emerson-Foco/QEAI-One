<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Notifica tarefas vencendo (requer cron: php artisan schedule:run a cada minuto).
Schedule::command('tasks:notify-due')->hourly();
Schedule::command('posts:publish-due')->everyMinute();
