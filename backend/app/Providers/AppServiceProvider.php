<?php

namespace App\Providers;

use App\Services\ChatService;
use App\Services\DocumentService;
use App\Services\YandexGptService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YandexGptService::class, function ($app) {
            return new YandexGptService();
        });

        $this->app->singleton(DocumentService::class, function ($app) {
            return new DocumentService($app->make(YandexGptService::class));
        });

        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService($app->make(YandexGptService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
