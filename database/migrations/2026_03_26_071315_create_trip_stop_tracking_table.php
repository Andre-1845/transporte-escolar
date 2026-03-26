<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripStopTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * ALTERAÇÕES:
     * - Nova tabela para controle granular de cada stop point na viagem
     * - Inclui campos para métricas de tempo e distância
     */
    public function up()
    {
        Schema::create('trip_stop_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('stop_id');
            $table->integer('stop_order');

            // Status do stop point
            $table->enum('status', ['pending', 'approaching', 'reached', 'passed'])
                ->default('pending');

            // Métricas de tempo
            $table->timestamp('first_approach_at')->nullable();
            $table->timestamp('reached_at')->nullable();
            $table->timestamp('passed_at')->nullable();

            // Métricas de distância
            $table->double('min_distance')->nullable();
            $table->double('distance_at_approach')->nullable();

            // Alertas
            $table->boolean('student_alert_sent')->default(false);
            $table->boolean('driver_alert_sent')->default(false);
            $table->timestamp('alert_sent_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['trip_id', 'stop_order']);
            $table->index(['trip_id', 'status']);
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->foreign('stop_id')->references('id')->on('route_stops');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trip_stop_tracking');
    }
}