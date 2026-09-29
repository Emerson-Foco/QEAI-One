<?php

namespace App\Support;

class PlanFeatures
{
    /**
     * Catálogo de recursos/limites da plataforma.
     * type: 'toggle' (ligado/desligado) ou 'limit' (quantidade).
     *
     * @return array<string, array{label:string, type:string}>
     */
    public static function catalog(): array
    {
        return [
            'users' => ['label' => 'Usuários', 'type' => 'limit'],
            'contacts' => ['label' => 'Contatos', 'type' => 'limit'],
            'storage_mb' => ['label' => 'Armazenamento (MB)', 'type' => 'limit'],
            'channels.email' => ['label' => 'Canal: E-mail', 'type' => 'toggle'],
            'channels.whatsapp' => ['label' => 'Canal: WhatsApp', 'type' => 'toggle'],
            'channels.sms' => ['label' => 'Canal: SMS', 'type' => 'toggle'],
            'ai' => ['label' => 'Chatbot com IA', 'type' => 'toggle'],
            'social' => ['label' => 'Publicação em redes sociais', 'type' => 'toggle'],
            'ads' => ['label' => 'Gestão de anúncios', 'type' => 'toggle'],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::catalog());
    }

    /** Configuração inicial do plano Free. */
    public static function freeDefaults(): array
    {
        return [
            'users' => ['enabled' => true, 'limit' => 2],
            'contacts' => ['enabled' => true, 'limit' => 500],
            'storage_mb' => ['enabled' => true, 'limit' => 500],
            'channels.email' => ['enabled' => true, 'limit' => null],
            'channels.whatsapp' => ['enabled' => false, 'limit' => null],
            'channels.sms' => ['enabled' => false, 'limit' => null],
            'ai' => ['enabled' => false, 'limit' => null],
            'social' => ['enabled' => false, 'limit' => null],
            'ads' => ['enabled' => false, 'limit' => null],
        ];
    }
}
