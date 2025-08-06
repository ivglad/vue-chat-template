<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Pgvector\Laravel\Distance;
use App\Jobs\GenerateChunkEmbedding;

class DocumentService
{
    private YandexGptService $yandexGptService;
    private GigaChatService $gigaChatService;
    private OpenRouterService $openRouterService;
    private SearchService $searchService;

    public function __construct(
        YandexGptService $yandexGptService, 
        GigaChatService $gigaChatService, 
        OpenRouterService $openRouterService,
        SearchService $searchService
    ) {
        $this->yandexGptService = $yandexGptService;
        $this->gigaChatService = $gigaChatService;
        $this->openRouterService = $openRouterService;
        $this->searchService = $searchService;
    }

    /**
     * Разбивает текст на чанки заданного размера с учетом перекрытия и завершением на конце предложения.
     *
     * Метод принимает входной текст и разбивает его на части (чанки) определенной длины,
     * стараясь завершить каждый чанк на конце предложения (после точки, восклицательного или
     * вопросительного знака). Также поддерживается перекрытие между чанками, чтобы сохранить
     * контекст. Пустые или слишком короткие чанки (менее 50 символов) отфильтровываются.
     *
     * @param string $text Входной текст для разбиения.
     * @param int $chunkSize Максимальный размер чанка в символах (по умолчанию 1000).
     * @param int $overlap Размер перекрытия между чанками в символах (по умолчанию 200).
     * @return array Массив чанков текста, отфильтрованный по минимальной длине.
     */
    public function splitTextIntoChunks(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        // Инициализация массива для хранения чанков
        $chunks = [];
        
        // Получение длины текста в символах с учетом многобайтовых кодировок (например, UTF-8)
        $textLength = mb_strlen($text);
        
        // Начальная позиция для разбиения текста
        $start = 0;

        // Цикл продолжается, пока не достигнут конец текста
        while ($start < $textLength) {
            // Определение предполагаемого конца текущего чанка
            $end = min($start + $chunkSize, $textLength);
            
            // Если конец чанка не совпадает с концом текста, пытаемся найти ближайший конец предложения
            if ($end < $textLength) {
                // Ищем ближайшую точку в диапазоне ±100 символов от предполагаемого конца чанка
                $nextDot = mb_strpos($text, '.', $end - 100);
                // Ищем ближайший восклицательный знак в том же диапазоне
                $nextExclamation = mb_strpos($text, '!', $end - 100);
                // Ищем ближайший вопросительный знак в том же диапазоне
                $nextQuestion = mb_strpos($text, '?', $end - 100);
                
                // Находим максимальную позицию конца предложения (если найдено)
                $sentenceEnd = max($nextDot, $nextExclamation, $nextQuestion);
                
                // Если конец предложения найден и находится в пределах ±100 символов от предполагаемого конца,
                // корректируем конец чанка, чтобы включить символ конца предложения
                if ($sentenceEnd !== false && $sentenceEnd < $end + 100) {
                    $end = $sentenceEnd + 1;
                }
            }
            
            // Извлекаем подстроку (чанк) от начальной позиции до конечной
            $chunk = mb_substr($text, $start, $end - $start);
            
            // Удаляем лишние пробелы в начале и конце чанка
            $chunks[] = trim($chunk);
            
            // Обновляем начальную позицию для следующего чанка, учитывая перекрытие
            // Перекрытие позволяет сохранить контекст между чанками
            $start = max($start + $chunkSize - $overlap, $end);
        }

        // Фильтруем чанки, оставляя только те, длина которых больше 50 символов
        // Это предотвращает включение слишком коротких фрагментов
        return array_filter($chunks, fn($chunk) => mb_strlen(trim($chunk)) > 50);
    }

    /**
     * Извлекает текст из файла в формате PDF, DOCX или DOC.
     *
     * Метод принимает путь к файлу и определяет его формат по расширению. Затем использует
     * соответствующие библиотеки для извлечения текста. Поддерживаются файлы PDF, DOCX и DOC.
     * Для DOC-файлов предполагается, что они могут быть конвертированы в DOCX или обработаны
     * через сторонние сервисы, так как прямое извлечение текста из DOC сложнее.
     *
     * @param string $filePath Путь к файлу (PDF, DOCX или DOC).
     * @return string|null Извлеченный текст или null в случае ошибки.
     */
    public function extractTextFromFile(string $filePath): ?string
    {
        try {
            // Проверяем, существует ли файл
            if (!file_exists($filePath)) {
                Log::error("File not found: {$filePath}");
                return null;
            }

            // Получаем расширение файла
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            switch ($extension) {
                case 'txt':
                    // Извлечение текста из TXT
                    $text = file_get_contents($filePath);
                    Log::info("TXT text extracted: " . ($text ? substr($text, 0, 100) : 'Empty'));
                    return $text ?: null;
                case 'pdf':
                    // Извлечение текста из PDF с использованием smalot/pdfparser
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    $text = $pdf->getText();
                    return $text ?: null;

                case 'docx':
                    // Извлечение текста из DOCX с использованием phpword
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . "\n";
                            }
                        }
                    }
                    return $text ?: null;

                case 'doc':
                    // Проверяем наличие LibreOffice
                    if (shell_exec('which soffice')) {
                        Log::info("Using LibreOffice for DOC conversion: {$filePath}");
                        // Формируем имя выходного файла на основе входного
                        $fileNameWithoutExt = pathinfo($filePath, PATHINFO_FILENAME);
                        $tempDocxPath = sys_get_temp_dir() . '/' . $fileNameWithoutExt . '.docx';
                        $command = "soffice --headless --convert-to docx --outdir " . escapeshellarg(sys_get_temp_dir()) . " " . escapeshellarg($filePath) . " 2>&1";
                        Log::info("Executing DOC conversion command: {$command}");
                        exec($command, $output, $returnVar);

                        // Даем небольшой таймаут для завершения конвертации
                        sleep(1);

                        if ($returnVar === 0 && file_exists($tempDocxPath)) {
                            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempDocxPath);
                            $text = '';
                            foreach ($phpWord->getSections() as $section) {
                                foreach ($section->getElements() as $element) {
                                    if (method_exists($element, 'getText')) {
                                        $text .= $element->getText() . "\n";
                                    }
                                }
                            }
                            unlink($tempDocxPath); // Удаляем временный файл
                            Log::info("DOC text extracted via LibreOffice: " . ($text ? substr($text, 0, 100) : 'Empty'));
                            return $text ? mb_convert_encoding($text, 'UTF-8', 'UTF-8') : null;
                        } else {
                            Log::error("Failed to convert DOC to DOCX: {$filePath}, Return code: {$returnVar}, Output: " . implode("\n", $output));
                        }
                    } else {
                        Log::warning("LibreOffice not installed, attempting alternative DOC extraction: {$filePath}");
                        // Альтернативный метод: использование antiword (если доступно)
                        if (shell_exec('which antiword')) {
                            $command = "antiword " . escapeshellarg($filePath) . " 2>&1";
                            Log::info("Executing antiword command: {$command}");
                            exec($command, $output, $returnVar);
                            if ($returnVar === 0) {
                                $text = implode("\n", $output);
                                Log::info("DOC text extracted via antiword: " . ($text ? substr($text, 0, 100) : 'Empty'));
                                return $text ? mb_convert_encoding($text, 'UTF-8', 'UTF-8') : null;
                            } else {
                                Log::error("Failed to extract DOC with antiword: {$filePath}, Return code: {$returnVar}, Output: " . implode("\n", $output));
                            }
                        } else {
                            Log::error("No DOC extraction tools available (LibreOffice or antiword): {$filePath}");
                        }
                    }
                    return null;

                default:
                    Log::error("Unsupported file format: {$extension}");
                    return null;
            }
        } catch (\Exception $e) {
            Log::error("Error extracting text from file {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Генерация эмбеддингов для документа с улучшенным разделением на чанки
     */
    public function generateEmbeddingsForDocument(Document $document): bool
    {
        try {
            // Очищаем старые эмбеддинги
            $document->embeddings()->delete();

            $content = $document->content;

            if (!$content) {
                Log::warning("No content found for document {$document->id}");
                $document->update(['processing_status' => 'failed']);
                return false;
            }

            // Устанавливаем статус обработки
            $document->update([
                'processing_status' => 'processing',
                'embeddings_generated' => false
            ]);

            // Используем улучшенное разделение на чанки
            $chunks = $this->getImprovedChunks($content);

            // Логируем информацию о качестве разделения
            $this->logChunkingQuality($document->id, $chunks);

            foreach ($chunks as $index => $chunk) {
                GenerateChunkEmbedding::dispatch($document->id, $index, $chunk)
                    ->delay(now()->addMilliseconds(1000 * $index));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error generating embeddings for document: ' . $e->getMessage());
            $document->update(['processing_status' => 'failed']);
            return false;
        }
    }

    /**
     * Получение улучшенных чанков с выбором оптимального метода
     */
    private function getImprovedChunks(string $content): array
    {
        return $this->getChunksByMethod($content);
    }

    /**
     * Публичный метод для получения чанков по выбранному методу
     * Используется как в DocumentService, так и в GenerateChunkEmbedding
     */
    public function getChunksByMethod(string $content, ?string $method = null): array
    {
        $chunkingMethod = $method ?? config('app.chunking_method', env('CHUNKING_METHOD', 'adaptive'));
        
        try {
            switch ($chunkingMethod) {
                // case 'adaptive':
                //     $qualityService = new \App\Services\ChunkQualityService();
                //     $adaptiveService = new \App\Services\AdaptiveChunkingService($qualityService);
                //     return $adaptiveService->adaptiveChunking($content, [
                //         'target_chunk_size' => (int)config('app.chunk_size', env('CHUNK_SIZE', 1000)),
                //         'quality_threshold' => (float)config('app.chunk_quality_threshold', env('CHUNK_QUALITY_THRESHOLD', 0.6))
                //     ]);
                    
                // case 'improved':
                //     $improvedService = new \App\Services\ImprovedDocumentService(
                //         $this->yandexGptService,
                //         $this->gigaChatService,
                //         $this->openRouterService
                //     );
                //     return $improvedService->splitTextIntoChunks(
                //         $content,
                //         (int)config('app.chunk_size', env('CHUNK_SIZE', 1000)),
                //         (int)config('app.chunk_overlap', env('CHUNK_OVERLAP', 200))
                //     );
                    
                // case 'semantic':
                //     $improvedService = new \App\Services\ImprovedDocumentService(
                //         $this->yandexGptService,
                //         $this->gigaChatService,
                //         $this->openRouterService
                //     );
                //     return $improvedService->semanticSplitTextIntoChunks(
                //         $content,
                //         (int)config('app.chunk_size', env('CHUNK_SIZE', 1000)),
                //         (int)config('app.chunk_overlap', env('CHUNK_OVERLAP', 200))
                //     );
                    
                default:
                    // Используем оригинальный метод как fallback
                    return $this->splitTextIntoChunks(
                        $content,
                        (int)config('app.chunk_size', env('CHUNK_SIZE', 1000)),
                        (int)config('app.chunk_overlap', env('CHUNK_OVERLAP', 200))
                    );
            }
        } catch (\Exception $e) {
            Log::error("Error in getChunksByMethod with method '{$chunkingMethod}': " . $e->getMessage());
            // Fallback к оригинальному методу
            return $this->splitTextIntoChunks($content);
        }
    }

    /**
     * Логирование информации о качестве разделения
     */
    private function logChunkingQuality(int $documentId, array $chunks): void
    {
        $qualityService = new \App\Services\ChunkQualityService();
        $totalQuality = 0;
        $lowQualityCount = 0;
        
        foreach ($chunks as $chunk) {
            $quality = $qualityService->evaluateChunkQuality($chunk);
            $overallScore = ($quality['completeness_score'] + $quality['semantic_coherence'] + 
                           $quality['information_density'] + $quality['boundary_quality']) / 4;
            $totalQuality += $overallScore;
            
            if ($overallScore < 0.5) {
                $lowQualityCount++;
            }
        }
        
        $averageQuality = count($chunks) > 0 ? $totalQuality / count($chunks) : 0;
        
        Log::info("Document {$documentId} chunking quality", [
            'total_chunks' => count($chunks),
            'average_quality' => round($averageQuality, 3),
            'low_quality_chunks' => $lowQualityCount,
            'average_length' => count($chunks) > 0 ? array_sum(array_map('mb_strlen', $chunks)) / count($chunks) : 0
        ]);
    }

    /**
     * Получить список доступных моделей
     */
    public function getAvailableModels(): array
    {
        return [
            'yandex' => 'Yandex GPT',
            'gigachat' => 'GigaChat',
            'openrouter' => 'OpenRouter'
        ];
    }

    /**
     * Ответ на вопрос по документам пользователя
     */
    public function answerQuestion(int $userId, string $question, ?string $model = null): ?string
    {
        // Если модель не указана, используем настройку по умолчанию из .env
        if ($model === null) {
            $model = config('app.default_ai_model', env('DEFAULT_AI_MODEL', 'yandex'));
        }

        // Получаем пользователя
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return "Пользователь не найден.";
        }

        // Используем унифицированный SearchService для поиска релевантных документов
        $relevantDocuments = $this->searchService->findRelevantDocuments($user, $question, 5);

        if ($relevantDocuments->isEmpty()) {
            return "Не удалось найти релевантную информацию в ваших документах.";
        }

        // Формируем контекст из найденных документов
        $context = $this->searchService->buildContext($relevantDocuments);

        // Выбираем сервис в зависимости от модели
        switch (strtolower($model)) {
            case 'gigachat':
                return $this->gigaChatService->generateResponse($context, $question, $userId);
            case 'openrouter':
                return $this->openRouterService->generateResponse($context, $question, $userId);
            case 'yandex':
            default:
                return $this->yandexGptService->generateResponse($context, $question, $userId);
        }
    }
} 