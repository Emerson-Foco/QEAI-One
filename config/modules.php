<?php

/*
|--------------------------------------------------------------------------
| Módulos do Q One
|--------------------------------------------------------------------------
| O núcleo do produto é composto por Dashboard, Leads e Gestão de CRM.
| Os demais módulos continuam no código e podem ser reativados aos poucos
| alterando QEAI_MODULES no .env (lista separada por vírgulas).
*/

return [
    'enabled' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('QEAI_MODULES', 'dashboard,leads,crm_fields,settings,logs'))
    ))),

    'catalog' => [
        'dashboard' => 'Dashboard',
        'leads' => 'Leads',
        'crm_fields' => 'Gestão de CRM',
        'settings' => 'Configurações e equipe',
        'logs' => 'Logs e auditoria',
        'companies' => 'Empresas',
        'pipeline' => 'Funil de vendas',
        'tasks' => 'Tarefas',
        'inbox' => 'Atendimento',
        'channels' => 'Canais',
        'forms' => 'Formulários',
        'social' => 'Redes sociais',
        'ads' => 'Anúncios',
        'integrations' => 'Integrações e API',
    ],
];
