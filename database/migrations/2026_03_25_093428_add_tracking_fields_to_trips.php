<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->double('last_distance')->nullable()->after('last_stop_id');
            $table->boolean('approaching_stop')->default(false)->after('last_distance');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['last_distance', 'approaching_stop']);
        });
    }
};