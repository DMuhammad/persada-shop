<?php

namespace App\Providers;

use App\Models\Stock\Stock;
use App\Observers\StockObserver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.url') && str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Stock::observe(StockObserver::class);

        Mail::extend('brevo', function (array $config = []) {
            $apiKey = $config['key'] ?? config('services.brevo.key');
            return (new BrevoTransportFactory())->create(
                new Dsn('brevo+api', 'default', $apiKey)
            );
        });
    }
}
