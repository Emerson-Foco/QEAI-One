<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Support\SocialPublisher;
use Illuminate\Console\Command;

class PublishDuePosts extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Publica posts agendados cujo horário já chegou.';

    public function handle(): int
    {
        $posts = Post::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            SocialPublisher::publish($post);
        }

        $this->info($posts->count() . ' post(s) processado(s).');

        return self::SUCCESS;
    }
}
