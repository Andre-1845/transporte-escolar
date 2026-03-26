<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectionFieldsToTripLocations extends Migration
{
    /**
     * Migration: Adiciona campos de direção e movimento
     *
     * ALTERAÇÕES:
     * - bearing: direção em graus (0-360) onde 0 = Norte, 90 = Leste
     * - speed: velocidade em km/h
     * - heading: direção cardinal (N, NE, E, SE, S, SW, W, NW)
     * - movement_status: parado, em_movimento, aproximando, afastando
     */
    public function up()
    {
        Schema::table('trip_locations', function (Blueprint $table) {
            $table->float('bearing')->nullable()->comment('Direção em graus (0-360)');
            $table->float('speed')->nullable()->comment('Velocidade em km/h');
            $table->string('heading', 2)->nullable()->comment('Direção cardinal');
            $table->enum('movement_status', ['stopped', 'moving', 'approaching', 'leaving'])
                ->nullable()->comment('Status de movimento relativo ao próximo stop');
        });
    }

    public function down()
    {
        Schema::table('trip_locations', function (Blueprint $table) {
            $table->dropColumn(['bearing', 'speed', 'heading', 'movement_status']);
        });
    }
}
