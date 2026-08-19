<?php

use App\Http\Controllers\Api\DemoTicketingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\TicketingWebhookController;
use Illuminate\Support\Facades\Route;

Route::apiResource('events', EventController::class)->only(['index', 'store', 'show', 'update']);
Route::post('events/{event}/registrations', [RegistrationController::class, 'store']);
Route::get('registrations/{registration:registration_reference}', [RegistrationController::class, 'show']);
Route::post('registrations/{registration:registration_reference}/demo-confirmation', DemoTicketingController::class);
Route::post('webhooks/ticketing', TicketingWebhookController::class);
