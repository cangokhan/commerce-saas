<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // XML Import Job - Her saat başı çalışır (XML_IMPORT_URL env var'ı set edilmeli)
        if (env('XML_IMPORT_URL')) {
            $schedule->command('import:products-xml')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();
        }

        // Queue worker restart - Her gün saat 02:00'de
        $schedule->command('queue:restart')
            ->dailyAt('02:00');

        // Cache temizleme - Her hafta Pazar günü saat 03:00'de
        $schedule->command('cache:clear')
            ->weeklyOn(0, '3:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

