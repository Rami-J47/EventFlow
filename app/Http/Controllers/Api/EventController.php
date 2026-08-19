<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $events = Event::query()->where('status', 'active')->where('event_date', '>=', now())
            ->whereRaw('(select count(*) from registrations where registrations.event_id = events.id) < events.capacity')
            ->withCount('registrations')->orderBy('event_date')->get();

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request): EventResource
    {
        return new EventResource(Event::query()->create($request->validated())->loadCount('registrations'));
    }

    public function show(Event $event): EventResource
    {
        return new EventResource($event->loadCount('registrations'));
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $event->update($request->validated());

        return new EventResource($event->refresh()->loadCount('registrations'));
    }
}
