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
        Schema::create('stop_time_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('route_id');
            $table->foreignId('from_stop_id');
            $table->foreignId('to_stop_id');

            $table->integer('avg_time_seconds');

            $table->string('period')->nullable(); // morning, afternoon...

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_time_metrics');
    }
};
