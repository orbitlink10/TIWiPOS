<?php

namespace App\Console;

use App\Models\Business;
use App\Services\SubscriptionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function (SubscriptionService $service) {
            Business::chunk(50, function ($businesses) use ($service) {
                foreach ($businesses as $business) {
                    $service->checkAndUpdate($business);
                }
            });
        })->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
