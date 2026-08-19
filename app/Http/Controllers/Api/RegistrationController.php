<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use DomainException;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function store(StoreRegistrationRequest $request, Event $event, RegistrationService $service): RegistrationResource|JsonResponse
    {
        try {
            $registration = $service->create($event, $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return (new RegistrationResource($registration->load('event')))->response()->setStatusCode(201);
    }

    public function show(Registration $registration): RegistrationResource
    {
        return new RegistrationResource($registration->load('event'));
    }
}
