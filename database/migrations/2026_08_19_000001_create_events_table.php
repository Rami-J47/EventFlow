<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->timestampTz('event_date');
            $table->unsignedInteger('capacity');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index(['status', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
