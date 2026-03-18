<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            $table->integer('last_stop_order')
                ->nullable()
                ->after('status');

            $table->integer('student_change_limit')
                ->default(2)
                ->after('last_stop_order');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            $table->dropColumn([
                'last_stop_order',
                'student_change_limit'
            ]);
        });
    }
};