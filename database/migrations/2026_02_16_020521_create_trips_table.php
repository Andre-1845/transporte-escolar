<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete()
                ->index();

            $table->foreignId('bus_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('school_route_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('driver_id')->nullable();

            $table->foreign('driver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('driver_id');

            $table->date('trip_date')->index();

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'finished',
                'cancelled'
            ])->default('scheduled')->index();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};