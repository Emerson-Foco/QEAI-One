<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('webhook_endpoints')->orderBy('id')->chunkById(100, function ($endpoints) {
            foreach ($endpoints as $endpoint) {
                $secret = (string) ($endpoint->secret ?? '');
                if ($secret === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($secret);

                    continue;
                } catch (Throwable) {
                    DB::table('webhook_endpoints')->where('id', $endpoint->id)->update([
                        'secret' => Crypt::encryptString($secret),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Mantém os segredos criptografados.
    }
};
