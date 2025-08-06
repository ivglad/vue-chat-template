<?php

namespace App\Providers;

use App\Services\ChatService;
use App\Services\DocumentService;
use App\Services\SearchService;
use App\Services\YandexGptService;
use App\Services\GigaChatService;
use App\Services\OpenRouterService;
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

        $this->app->singleton(GigaChatService::class, function ($app) {
            return new GigaChatService();
        });

        $this->app->singleton(OpenRouterService::class, function ($app) {
            return new OpenRouterService();
        });

        $this->app->singleton(SearchService::class, function ($app) {
            return new SearchService($app->make(YandexGptService::class));
        });

        $this->app->singleton(DocumentService::class, function ($app) {
            return new DocumentService(
                $app->make(YandexGptService::class),
                $app->make(GigaChatService::class),
                $app->make(OpenRouterService::class),
                $app->make(SearchService::class)
            );
        });

        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService(
                $app->make(YandexGptService::class),
                $app->make(GigaChatService::class),
                $app->make(OpenRouterService::class),
                $app->make(SearchService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //\URL::forceScheme('https'); // Принудительно HTTPS для всех URL
    }
}
