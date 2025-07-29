<?php

namespace App\Console\Commands;

use App\Services\YandexGptService;
use Illuminate\Console\Command;

class TestYandexGptCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:yandex-gpt {--embeddings} {--completion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование интеграции с YandexGPT';

    public function handle(YandexGptService $yandexGptService)
    {
        $this->info('🚀 Тестирование YandexGPT интеграции...');
        
        if ($this->option('embeddings') || (!$this->option('completion') && !$this->option('embeddings'))) {
            $this->testEmbeddings($yandexGptService);
        }
        
        if ($this->option('completion') || (!$this->option('completion') && !$this->option('embeddings'))) {
            $this->testCompletion($yandexGptService);
        }
        
        $this->info('✅ Тестирование завершено!');
    }

    private function testEmbeddings(YandexGptService $yandexGptService)
    {
        $this->info("\n📊 Тестирование генерации эмбеддингов...");
        
        $testText = "Машинное обучение - это область искусственного интеллекта, которая изучает алгоритмы и статистические модели.";
        
        $embedding = $yandexGptService->generateEmbeddings($testText);
        
        if ($embedding) {
            $this->info("✅ Эмбеддинги сгенерированы успешно");
            $this->info("📐 Размерность вектора: " . count($embedding));
            $this->info("🔢 Первые 5 значений: " . implode(', ', array_slice($embedding, 0, 5)));
        } else {
            $this->error("❌ Ошибка при генерации эмбеддингов");
        }
    }

    private function testCompletion(YandexGptService $yandexGptService)
    {
        $this->info("\n🤖 Тестирование генерации ответов...");
        
        $context = "Laravel - это PHP фреймворк для веб-разработки. Он использует архитектуру MVC и предоставляет множество инструментов для быстрой разработки.";
        $question = "Что такое Laravel?";
        
        $response = $yandexGptService->generateResponse($context, $question);
        
        if ($response) {
            $this->info("✅ Ответ сгенерирован успешно");
            $this->info("💬 Ответ: " . $response);
        } else {
            $this->error("❌ Ошибка при генерации ответа");
        }
    }
}
