<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'Dflix',
                'base_url' => 'https://movies.discoveryftp.net',
                'scraper_type' => 'dflix',
                'is_active' => true,
                'priority' => 1,
            ],
            [
                'name' => 'DhakaFlix (Movie)',
                'base_url' => 'http://172.16.50.14',
                'scraper_type' => 'dhakaflix',
                'is_active' => true,
                'priority' => 2,
            ],
            [
                'name' => 'DhakaFlix (Series)',
                'base_url' => 'http://172.16.50.12',
                'scraper_type' => 'dhakaflix',
                'is_active' => true,
                'priority' => 3,
            ],
            [
                'name' => 'RoarZone',
                'base_url' => 'https://play.roarzone.info',
                'scraper_type' => 'roarzone',
                'is_active' => true,
                'priority' => 4,
            ],
            [
                'name' => 'FTPBD',
                'base_url' => 'http://media.ftpbd.net:8096',
                'scraper_type' => 'ftpbd',
                'is_active' => false,
                'priority' => 5,
            ],
            [
                'name' => 'CircleFTP',
                'base_url' => 'http://new.circleftp.net:5000/api',
                'scraper_type' => 'circleftp',
                'is_active' => true,
                'priority' => 6,
            ],
            [
                'name' => 'ICC FTP',
                'base_url' => 'http://10.16.100.244',
                'scraper_type' => 'iccftp',
                'is_active' => true,
                'priority' => 7,
            ],
            [
                'name' => 'ihub',
                'base_url' => 'http://ihub.live/',
                'scraper_type' => 'ihub',
                'is_active' => true,
                'priority' => 8,
            ],
        ];

        $now = Carbon::now();

        foreach ($sources as $source) {
            DB::table('sources')->updateOrInsert(
                ['base_url' => $source['base_url']],
                array_merge($source, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
