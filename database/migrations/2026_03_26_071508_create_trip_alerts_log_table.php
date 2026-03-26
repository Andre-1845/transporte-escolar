<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripAlertsLogTable extends Migration
{
    /**
     * Migration: Cria tabela de log de alertas de viagem
     *
     * FUNÇÃO: Armazena histórico de todos os alertas enviados durante a viagem
     *
     * CAMPOS:
     * - id: Identificador único
     * - trip_id: ID da viagem
     * - stop_id: ID do stop point (pode ser null para alertas gerais)
     * - user_id: ID do usuário que recebeu (motorista ou aluno)
     * - alert_type: Tipo do alerta (approaching, reached, passed, end_warning, broadcast)
     * - distance_at_alert: Distância no momento do alerta (em metros)
     * - metadata: Dados adicionais em JSON (opcional)
     * - sent_at: Data e hora do envio
     * - created_at: Data de criação do registro
     */
    public function up()
    {
        Schema::create('trip_alerts_log', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('stop_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // Tipo do alerta
            $table->enum('alert_type', [
                'approaching',  // Aproximação do stop point
                'reached',      // Chegada no stop point
                'passed',       // Passou pelo stop point sem parar
                'end_warning',  // Aviso de fim de rota
                'broadcast',    // Broadcast geral
                'driver_alert', // Alerta específico para motorista
                'student_alert' // Alerta específico para aluno
            ])->default('approaching');

            // Métricas
            $table->double('distance_at_alert')->nullable()->comment('Distância em metros no momento do alerta');
            $table->json('metadata')->nullable()->comment('Dados adicionais em JSON');

            // Status e timestamps
            $table->boolean('delivered')->default(false)->comment('Se foi entregue com sucesso');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            // Índices para consultas rápidas
            $table->index(['trip_id', 'alert_type']);
            $table->index(['trip_id', 'user_id']);
            $table->index(['stop_id', 'alert_type']);
            $table->index('sent_at');

            // Chaves estrangeiras
            $table->foreign('trip_id')
                ->references('id')
                ->on('trips')
                ->onDelete('cascade');

            $table->foreign('stop_id')
                ->references('id')
                ->on('route_stops')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trip_alerts_log');
    }
}
