<?php

$siteUrl = rtrim(trim((string) env('SITE_URL', '')), '/');
$termsUrl = trim((string) env('LEGAL_TERMS_URL', ''));
$privacyUrl = trim((string) env('LEGAL_PRIVACY_URL', ''));

return [
    'terms_version' => '2026-10-27',
    'site_url' => $siteUrl,
    'terms_url' => $termsUrl !== '' ? $termsUrl : ($siteUrl !== '' ? $siteUrl . '/termos' : ''),
    'privacy_url' => $privacyUrl !== '' ? $privacyUrl : ($siteUrl !== '' ? $siteUrl . '/privacidade' : ''),
    'require_acceptance' => env('LEGAL_REQUIRE_ACCEPTANCE', env('APP_ENV') === 'production'),
];
