<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('trip_stop_alerts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('trip_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('route_stop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('sent_at');

            $table->unique([
                'trip_id',
                'student_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_stop_alerts');
    }
};