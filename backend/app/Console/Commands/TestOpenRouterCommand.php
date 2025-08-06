<?php

namespace App\Console\Commands;

use App\Services\OpenRouterService;
use Illuminate\Console\Command;

class TestOpenRouterCommand extends Command
{
    protected $signature = 'openrouter:test {--model= : Модель для тестирования (по умолчанию из конфига)}';
    protected $description = 'Тестирует подключение к OpenRouter API';

    public function handle(OpenRouterService $openRouterService)
    {
        $model = $this->option('model');
        
        $this->info('🧪 Тестируем подключение к OpenRouter API...');
        
        if ($model) {
            $this->info("Модель: {$model}");
        } else {
            $defaultModel = config('services.openrouter.default_model', 'qwen/qwen3-coder:free');
            $this->info("Модель: {$defaultModel} (по умолчанию)");
        }
        
        $this->line('');

        try {
            // Тестируем подключение
            $result = $openRouterService->testConnection($model);
            
            if ($result['success']) {
                $this->info('✅ ' . $result['message']);
                
                // Дополнительно тестируем генерацию ответа
                $this->info('🔄 Тестируем генерацию ответа...');
                $response = $openRouterService->generateResponse('', 'Привет! Как дела?', null, $model);
                
                if ($response) {
                    $this->info('✅ Ответ получен:');
                    $this->line($response);
                } else {
                    $this->error('❌ Не удалось получить ответ');
                }
                
                // Показываем доступные модели
                $this->line('');
                $this->info('📋 Доступные модели:');
                $models = $openRouterService->getAvailableModels();
                foreach ($models as $modelId => $modelName) {
                    $this->line("  • {$modelId} - {$modelName}");
                }
                
            } else {
                $this->error('❌ ' . $result['message']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}