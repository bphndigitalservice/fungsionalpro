<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One command deployment';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $this->info('Installing FungsionalPro ...');

        // 1. Migrate region reference
        $this->migrateRegionReference();

        // 2. Migrate the rest of remaining tables and its seeds
        $this->call('migrate');
        $this->call('shield:install', [
            '--fresh',
        ]);
        $this->call('db:seed');

    }

    public function migrateRegionReference(): void
    {
        if ($this->isRegionDatabaseDoesntExists()) {
            $sqlPath = database_path('sql');
            // Whitelist of allowed SQL files to prevent unauthorized execution
            $allowedFiles = [
                'regions.sql',
                'seed_data.sql',
                'provinces.sql',
                'regencies.sql',
            ];

            foreach (glob($sqlPath.DIRECTORY_SEPARATOR.'*.sql') as $sql) {
                $filename = basename($sql);
                if (!in_array($filename, $allowedFiles, true)) {
                    $this->warn("Skipping unauthorized SQL file: {$filename}");
                    continue;
                }

                $this->info("Migrating {$sql}");
                DB::unprepared(file_get_contents($sql));
            }
        }
    }

    public function isRegionDatabaseDoesntExists(): bool
    {
        return DB::table('reg_provinces')->doesntExist() && DB::table('reg_regencies')->doesntExist() && DB::table('reg_districts')->doesntExist();
    }
}
