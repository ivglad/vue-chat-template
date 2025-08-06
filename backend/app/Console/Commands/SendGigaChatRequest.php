<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\GigaChatAuthService;

class SendGigaChatRequest extends Command
{
    protected $signature = 'gigachat:send';
    protected $description = 'Отправляет запрос к GigaChat API и выводит ответ';

    public function handle(GigaChatAuthService $authService)
    {
        try {
            // Получаем токен через сервис
            $token = $authService->getToken();

            if (!$token) {
                $this->error('Не удалось получить токен доступа.');
                return 1;
            }

            $this->info('Токен получен. Отправляю запрос...');

            // Отправляем запрос к GigaChat
            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withoutVerifying() // ⚠️ Только для dev
                ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                    'model' => 'GigaChat',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Привет! Как дела?'
                        ]
                    ],
                    'stream' => false,
                    'repetition_penalty' => 1
                ]);

            if ($response->successful()) {
                $this->info('✅ Ответ от GigaChat:');
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error('Ошибка API: ' . $response->status());
                $this->line($response->body());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}