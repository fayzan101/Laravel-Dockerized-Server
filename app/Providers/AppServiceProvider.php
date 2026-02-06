<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
        public function register(): void
    {
    }

        public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            static $printed = false;

            if ($printed || $event->command !== 'serve') {
                return;
            }

            $printed = true;

            Artisan::call('route:list', [
                '--path' => 'api',
            ]);

            $output = trim(Artisan::output());

            if ($output !== '') {
                $event->output->writeln('');
                $event->output->writeln('API Routes:');
                $event->output->writeln($output);
                $event->output->writeln('');
            }
        });
    }
}
