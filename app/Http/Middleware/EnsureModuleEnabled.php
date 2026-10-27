<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /** @var array<string, string> */
    private array $modulesByRoute = [
        'member.org.companies.' => 'companies',
        'member.org.pipeline.' => 'pipeline',
        'member.org.deals.' => 'pipeline',
        'member.org.tasks.' => 'tasks',
        'member.org.forms.' => 'forms',
        'member.org.channels.' => 'channels',
        'member.org.inbox.' => 'inbox',
        'member.org.social.' => 'social',
        'member.org.ads.' => 'ads',
        'member.org.integrations.' => 'integrations',
    ];

    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        if ($module !== null && ! Modules::enabled($module)) {
            abort(404);
        }

        $route = $request->route()?->getName();

        if (is_string($route)) {
            foreach ($this->modulesByRoute as $prefix => $module) {
                if (str_starts_with($route, $prefix) && ! Modules::enabled($module)) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
