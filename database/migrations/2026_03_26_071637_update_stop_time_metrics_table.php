<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStopTimeMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * ALTERAÇÕES:
     * - Garante que a tabela stop_time_metrics existe com a estrutura correta
     * - Armazena médias de tempo entre stops por período
     */
    public function up()
    {
        if (!Schema::hasTable('stop_time_metrics')) {
            Schema::create('stop_time_metrics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('route_id');
                $table->unsignedBigInteger('from_stop_id');
                $table->unsignedBigInteger('to_stop_id');
                $table->integer('avg_time_seconds')->default(120); // tempo médio em segundos
                $table->enum('period', ['morning', 'afternoon', 'evening', 'night']);
                $table->integer('sample_count')->default(1); // número de amostras
                $table->timestamps();

                $table->unique(['route_id', 'from_stop_id', 'to_stop_id', 'period'], 'stop_metrics_unique');
                $table->index(['route_id', 'period']);
            });
        } else {
            // Se já existe, garante que tem os campos necessários
            Schema::table('stop_time_metrics', function (Blueprint $table) {
                if (!Schema::hasColumn('stop_time_metrics', 'sample_count')) {
                    $table->integer('sample_count')->default(1);
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stop_time_metrics');
    }
}