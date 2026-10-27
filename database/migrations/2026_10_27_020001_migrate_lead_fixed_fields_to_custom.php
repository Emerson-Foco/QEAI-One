<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            'nome' => ['Nome', 'text', true, 0],
            'email' => ['E-mail', 'email', true, 3],
            'telefone' => ['Telefone', 'phone', true, 4],
            'whatsapp' => ['WhatsApp', 'phone', false, 5],
            'origem' => ['Origem', 'text', true, 6],
            'data_inicial' => ['Data inicial', 'date', true, 7],
            'proximo_contato' => ['Próximo contato', 'date', true, 8],
            'observacoes' => ['Observações', 'textarea', false, 9],
            'cargo' => ['Cargo', 'text', false, 10],
        ];

        $legacyMap = [
            'nome' => 'name',
            'email' => 'email',
            'telefone' => 'phone',
            'whatsapp' => 'whatsapp',
            'origem' => 'source',
            'observacoes' => 'notes',
            'cargo' => 'job_title',
        ];

        foreach (DB::table('organizations')->orderBy('id')->pluck('id') as $organizationId) {
            $existing = DB::table('custom_fields')
                ->where('organization_id', $organizationId)
                ->where('entity', 'contact')
                ->pluck('key')
                ->all();

            foreach ($definitions as $key => [$label, $type, $showInList, $position]) {
                if (in_array($key, $existing, true)) {
                    continue;
                }

                DB::table('custom_fields')->insert([
                    'organization_id' => $organizationId,
                    'entity' => 'contact',
                    'field_id' => 'FLD-'.strtoupper((string) Str::ulid()),
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'options' => null,
                    'required' => false,
                    'show_in_list' => $showInList,
                    'is_system' => true,
                    'is_active' => true,
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('contacts')
                ->where('organization_id', $organizationId)
                ->orderBy('id')
                ->chunkById(200, function ($contacts) use ($legacyMap) {
                    foreach ($contacts as $contact) {
                        $original = json_decode((string) $contact->custom, true) ?: [];
                        $custom = $original;

                        foreach ($legacyMap as $key => $column) {
                            if (array_key_exists($key, $custom)) {
                                continue;
                            }
                            $value = $contact->{$column} ?? null;
                            if ($value !== null && $value !== '') {
                                $custom[$key] = $value;
                            }
                        }

                        if ($custom !== $original) {
                            DB::table('contacts')->where('id', $contact->id)->update([
                                'custom' => json_encode($custom, JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Migração aditiva: mantém os campos e dados para preservar o histórico.
    }
};
