<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketingWebhookService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TicketingWebhookController extends Controller
{
    public function __invoke(Request $request, TicketingWebhookService $service): JsonResponse
    {
        $raw = $request->getContent();
        $secret = (string) config('services.ticketing.webhook_secret');
        $provided = (string) $request->header('X-Webhook-Signature');
        if ($secret === '' || $provided === '' || ! hash_equals(hash_hmac('sha256', $raw, $secret), $provided)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }
        $validator = Validator::make($request->json()->all(), [
            'event' => ['required', Rule::in(['ticket.confirmed'])], 'registration_reference' => ['required', 'string', 'max:20'],
            'ticket_id' => ['required', 'string', 'max:255'], 'status' => ['required', Rule::in(['confirmed'])],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid webhook payload.', 'errors' => $validator->errors()], 422);
        }
        try {
            $processed = $service->process($validator->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['message' => $processed ? 'Webhook processed.' : 'Webhook already processed.', 'duplicate' => ! $processed]);
    }
}
