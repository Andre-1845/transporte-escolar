<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldTripLocations extends Command
{
    protected $signature = 'trip_locations:clean';
    protected $description = 'Remove localizações antigas das viagens';

    public function handle()
    {
        $days = 5;

        $deleted = DB::table('trip_locations')
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->limit(10000)
            ->delete();

        $this->info("Registros deletados: " . $deleted);
    }
}