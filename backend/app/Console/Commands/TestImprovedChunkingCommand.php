<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdaptiveChunkingService;
use App\Services\ChunkQualityService;
use App\Services\ImprovedDocumentService;

class TestImprovedChunkingCommand extends Command
{
    protected $signature = 'test:improved-chunking {--file=} {--method=adaptive}';
    protected $description = 'Тестирование улучшенного алгоритма разделения текста на чанки';

    public function handle()
    {
        $filePath = $this->option('file');
        $method = $this->option('method');

        // Тестовый текст, если файл не указан
        $testText = $filePath ? file_get_contents($filePath) : $this->getTestText();

        $this->info("Тестирование разделения текста на чанки");
        $this->info("Метод: " . $method);
        $this->info("Длина текста: " . mb_strlen($testText) . " символов");
        $this->line("");

        switch ($method) {
            case 'adaptive':
                $this->testAdaptiveChunking($testText);
                break;
            case 'improved':
                $this->testImprovedChunking($testText);
                break;
            case 'semantic':
                $this->testSemanticChunking($testText);
                break;
            case 'compare':
                $this->compareAllMethods($testText);
                break;
            default:
                $this->error("Неизвестный метод: $method");
                return;
        }
    }

    private function testAdaptiveChunking(string $text)
    {
        $qualityService = new ChunkQualityService();
        $adaptiveService = new AdaptiveChunkingService($qualityService);

        $this->info("=== Адаптивное разделение ===");
        
        $chunks = $adaptiveService->adaptiveChunking($text, [
            'target_chunk_size' => 800,
            'quality_threshold' => 0.6
        ]);

        $this->displayChunks($chunks);
        
        $analysis = $adaptiveService->analyzeChunkingQuality($chunks);
        $this->displayQualityAnalysis($analysis);
    }

    private function testImprovedChunking(string $text)
    {
        $improvedService = new ImprovedDocumentService(
            app(\App\Services\YandexGptService::class),
            app(\App\Services\GigaChatService::class),
            app(\App\Services\OpenRouterService::class)
        );

        $this->info("=== Улучшенное разделение ===");
        
        $chunks = $improvedService->splitTextIntoChunks($text, 800, 150);
        $this->displayChunks($chunks);
    }

    private function testSemanticChunking(string $text)
    {
        $improvedService = new ImprovedDocumentService(
            app(\App\Services\YandexGptService::class),
            app(\App\Services\GigaChatService::class),
            app(\App\Services\OpenRouterService::class)
        );

        $this->info("=== Семантическое разделение ===");
        
        $chunks = $improvedService->semanticSplitTextIntoChunks($text, 800, 150);
        $this->displayChunks($chunks);
    }

    private function compareAllMethods(string $text)
    {
        $this->info("=== Сравнение всех методов ===");
        
        // Оригинальный метод
        $originalService = app(\App\Services\DocumentService::class);
        $originalChunks = $originalService->splitTextIntoChunks($text, 800, 150);
        
        // Улучшенный метод
        $improvedService = new ImprovedDocumentService(
            app(\App\Services\YandexGptService::class),
            app(\App\Services\GigaChatService::class),
            app(\App\Services\OpenRouterService::class)
        );
        $improvedChunks = $improvedService->splitTextIntoChunks($text, 800, 150);
        
        // Адаптивный метод
        $qualityService = new ChunkQualityService();
        $adaptiveService = new AdaptiveChunkingService($qualityService);
        $adaptiveChunks = $adaptiveService->adaptiveChunking($text, ['target_chunk_size' => 800]);

        $this->table(['Метод', 'Количество чанков', 'Средняя длина', 'Средняя оценка качества'], [
            [
                'Оригинальный',
                count($originalChunks),
                $this->calculateAverageLength($originalChunks),
                $this->calculateAverageQuality($originalChunks, $qualityService)
            ],
            [
                'Улучшенный',
                count($improvedChunks),
                $this->calculateAverageLength($improvedChunks),
                $this->calculateAverageQuality($improvedChunks, $qualityService)
            ],
            [
                'Адаптивный',
                count($adaptiveChunks),
                $this->calculateAverageLength($adaptiveChunks),
                $this->calculateAverageQuality($adaptiveChunks, $qualityService)
            ]
        ]);
    }

    private function displayChunks(array $chunks)
    {
        $this->info("Количество чанков: " . count($chunks));
        $this->line("");

        foreach ($chunks as $i => $chunk) {
            $this->info("Чанк " . ($i + 1) . " (длина: " . mb_strlen($chunk) . "):");
            $this->line(mb_substr($chunk, 0, 200) . (mb_strlen($chunk) > 200 ? '...' : ''));
            $this->line("");
        }
    }

    private function displayQualityAnalysis(array $analysis)
    {
        $this->info("=== Анализ качества ===");
        $this->info("Общее количество чанков: " . $analysis['total_chunks']);
        $this->info("Средняя оценка качества: " . round($analysis['average_quality'], 3));
        $this->line("");

        if (!empty($analysis['recommendations'])) {
            $this->warn("Рекомендации:");
            foreach ($analysis['recommendations'] as $recommendation) {
                $this->line("• " . $recommendation);
            }
            $this->line("");
        }

        // Показываем детали для чанков с низким качеством
        $lowQualityChunks = array_filter($analysis['chunk_details'], fn($chunk) => $chunk['overall_score'] < 0.5);
        
        if (!empty($lowQualityChunks)) {
            $this->warn("Чанки с низким качеством:");
            foreach ($lowQualityChunks as $chunk) {
                $this->line("Чанк {$chunk['chunk_index']}: оценка {$chunk['overall_score']}");
                if (!empty($chunk['suggestions'])) {
                    foreach ($chunk['suggestions'] as $suggestion) {
                        $this->line("  - " . $suggestion);
                    }
                }
            }
        }
    }

    private function calculateAverageLength(array $chunks): int
    {
        if (empty($chunks)) return 0;
        return (int)(array_sum(array_map('mb_strlen', $chunks)) / count($chunks));
    }

    private function calculateAverageQuality(array $chunks, ChunkQualityService $qualityService): float
    {
        if (empty($chunks)) return 0;
        
        $totalScore = 0;
        foreach ($chunks as $chunk) {
            $quality = $qualityService->evaluateChunkQuality($chunk);
            $totalScore += ($quality['completeness_score'] + $quality['semantic_coherence'] + 
                          $quality['information_density'] + $quality['boundary_quality']) / 4;
        }
        
        return round($totalScore / count($chunks), 3);
    }

    private function getTestText(): string
    {
        return "Искусственный интеллект представляет собой одну из наиболее динамично развивающихся областей современной науки и технологий. Эта дисциплина объединяет в себе достижения компьютерных наук, математики, когнитивной психологии и нейробиологии.

Машинное обучение является ключевой составляющей искусственного интеллекта. Оно позволяет компьютерным системам автоматически улучшать свою производительность на основе опыта. Существует несколько основных подходов к машинному обучению: обучение с учителем, обучение без учителя и обучение с подкреплением.

Глубокое обучение, основанное на искусственных нейронных сетях, произвело революцию во многих областях применения ИИ. Сверточные нейронные сети показали выдающиеся результаты в области компьютерного зрения, позволяя системам распознавать объекты на изображениях с точностью, превышающей человеческую.

Обработка естественного языка является еще одной важной областью применения искусственного интеллекта. Современные языковые модели, такие как трансформеры, способны генерировать связный текст, переводить между языками и отвечать на сложные вопросы.

Этические аспекты развития искусственного интеллекта становятся все более актуальными. Вопросы справедливости алгоритмов, прозрачности принятия решений и влияния на рынок труда требуют серьезного рассмотрения со стороны исследователей, разработчиков и регулирующих органов.";
    }
}