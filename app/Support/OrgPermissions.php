<?php

namespace App\Support;

class OrgPermissions
{
    /** @return array<string, string> */
    public static function catalog(): array
    {
        return [
            'org.settings' => 'Configurações da organização',
            'org.members' => 'Membros e grupos de acesso',
            'org.billing' => 'Cobrança e plano',
            'org.logs' => 'Logs da organização',
            'org.channels' => 'Canais e integrações',
            'org.leads' => 'Leads e CRM',
            'org.conversations' => 'Atendimento',
            'org.automations' => 'Automações',
            'org.social' => 'Redes sociais (publicação)',
            'org.ads' => 'Anúncios',
            'org.reports' => 'Relatórios',
            'org.api_keys' => 'Chaves de API',
        ];
    }

    /** @return array<int, array{name:string, slug:string, permissions:array, is_system:bool}> */
    public static function defaults(): array
    {
        $all = array_keys(self::catalog());

        return [
            ['name' => 'Proprietário', 'slug' => 'owner', 'permissions' => ['*'], 'is_system' => true],
            ['name' => 'Administrador', 'slug' => 'admin', 'permissions' => $all, 'is_system' => true],
            ['name' => 'Gerente', 'slug' => 'manager', 'permissions' => ['org.channels', 'org.leads', 'org.conversations', 'org.automations', 'org.social', 'org.ads', 'org.reports', 'org.logs'], 'is_system' => true],
            ['name' => 'Agente', 'slug' => 'agent', 'permissions' => ['org.leads', 'org.conversations', 'org.reports'], 'is_system' => true],
        ];
    }
}
