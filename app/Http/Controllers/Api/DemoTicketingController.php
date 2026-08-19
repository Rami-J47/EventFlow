<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DemoTicketingController extends Controller
{
    public function __invoke(Registration $registration): JsonResponse
    {
        $payload = ['event' => 'ticket.confirmed', 'registration_reference' => $registration->registration_reference,
            'ticket_id' => $registration->ticket_id ?? 'TCK-'.Str::upper(Str::random(8)), 'status' => 'confirmed'];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $raw, (string) config('services.ticketing.webhook_secret'));
        $request = Request::create('/api/webhooks/ticketing', 'POST', server: [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], content: $raw);
        $response = app()->handle($request);

        return response()->json(json_decode($response->getContent(), true), $response->getStatusCode());
    }
}
