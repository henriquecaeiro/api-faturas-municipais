<?php

namespace App\Console;

use App\Console\Commands\MunicipiosAtualizarSituacaoCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        MunicipiosAtualizarSituacaoCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('billing:sync-municipality-status')
            ->dailyAt('00:15')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
