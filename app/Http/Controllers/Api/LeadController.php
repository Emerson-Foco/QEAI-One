<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LeadIntake;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $organization = $request->attributes->get('api_organization');
        $key = $request->attributes->get('api_key');

        $payload = $request->json()->all() ?: $request->all();

        if (! empty($payload['email']) && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => ['code' => 'validation_error', 'message' => 'E-mail inválido.']], 422);
        }

        $contact = LeadIntake::create($organization, $payload, 'api', [
            'api_key_id' => $key?->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'data' => [
                'id' => $contact->id,
                'lead_id' => $contact->lead_id,
                'name' => $contact->displayName(),
                'email' => $contact->email,
            ],
        ], 201);
    }
}
