<?php

namespace Tests\Feature;

use App\Support\Installer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_installer_when_not_installed(): void
    {
        $this->withMarker(false, fn () => $this->get('/')->assertRedirect(route('install.show')));
    }

    public function test_landing_page_renders_when_installed(): void
    {
        $this->withMarker(true, function () {
            $this->get('/')->assertOk()->assertSee('QEAI One');
        });
    }

    private function withMarker(bool $installed, callable $callback): void
    {
        $marker = Installer::markerPath();
        $existed = is_file($marker);
        $backup = $existed ? (string) file_get_contents($marker) : null;

        if ($installed) {
            Installer::markInstalled();
        } else {
            @unlink($marker);
        }

        try {
            $callback();
        } finally {
            if ($backup !== null) {
                file_put_contents($marker, $backup);
            } else {
                @unlink($marker);
            }
        }
    }
}
