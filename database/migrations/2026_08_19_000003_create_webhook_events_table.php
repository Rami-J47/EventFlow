<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 64)->unique();
            $table->string('event_type');
            $table->string('registration_reference', 20)->index();
            $table->string('ticket_id');
            $table->jsonb('payload');
            $table->string('processing_status', 20);
            $table->timestampTz('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
