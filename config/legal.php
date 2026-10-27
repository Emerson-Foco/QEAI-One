<?php

return [
    'terms_version' => '2026-10-27',
    'terms_url' => env('LEGAL_TERMS_URL', 'https://qeai.com.br/termos'),
    'privacy_url' => env('LEGAL_PRIVACY_URL', 'https://qeai.com.br/privacidade'),
    'require_acceptance' => env('LEGAL_REQUIRE_ACCEPTANCE', env('APP_ENV') === 'production'),
];
