<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update market prices every minute for real-time fluctuation
        $schedule->call(function () {
            try {
                defaultCurrencyDataProvider()->updateMarkets();
            } catch (\Exception $e) {
                \Log::error('Market data update failed: ' . $e->getMessage());
            }
        })->everyMinute()->name('update-market-prices');
        
        // Update crypto prices every minute
        $schedule->call(function () {
            try {
                defaultCurrencyDataProvider()->updateCryptoPrice();
            } catch (\Exception $e) {
                \Log::error('Crypto price update failed: ' . $e->getMessage());
            }
        })->everyMinute()->name('update-crypto-prices');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
