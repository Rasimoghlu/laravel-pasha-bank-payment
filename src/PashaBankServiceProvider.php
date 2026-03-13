<?php

namespace Sarkhanrasimoghlu\PashaBank;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Sarkhanrasimoghlu\PashaBank\Configuration\PashaBankConfiguration;
use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\HttpClientInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCreated;
use Sarkhanrasimoghlu\PashaBank\Http\GuzzleHttpClient;
use Sarkhanrasimoghlu\PashaBank\Listeners\SaveTransactionListener;
use Sarkhanrasimoghlu\PashaBank\Services\PashaBankService;

class PashaBankServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pasha-bank.php', 'pasha-bank');

        $this->app->singleton(ConfigurationInterface::class, function ($app) {
            $config = PashaBankConfiguration::fromArray($app['config']->get('pasha-bank', []));
            $config->validate();

            return $config;
        });

        $this->app->singleton(HttpClientInterface::class, function ($app) {
            return new GuzzleHttpClient($app->make(ConfigurationInterface::class));
        });

        $this->app->singleton(PashaBankServiceInterface::class, function ($app) {
            return new PashaBankService(
                httpClient: $app->make(HttpClientInterface::class),
                configuration: $app->make(ConfigurationInterface::class),
                logger: $app->make(LoggerInterface::class),
                events: $app->make(Dispatcher::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pasha-bank.php' => config_path('pasha-bank.php'),
            ], 'pasha-bank-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'pasha-bank-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/pasha-bank.php');

        $this->app->make(Dispatcher::class)->listen(
            PaymentCreated::class,
            SaveTransactionListener::class,
        );
    }

    public function provides(): array
    {
        return [
            ConfigurationInterface::class,
            HttpClientInterface::class,
            PashaBankServiceInterface::class,
        ];
    }
}
