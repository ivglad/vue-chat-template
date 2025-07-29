<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ChatService;
use Illuminate\Console\Command;

class TestChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:test {message} {--user-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование функциональности чата';

    /**
     * Execute the console command.
     */
    public function handle(ChatService $chatService): int
    {
        $message = $this->argument('message');
        $userId = $this->option('user-id');

        $user = User::find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        $this->info("Отправляем сообщение от пользователя: {$user->name}");
        $this->info("Сообщение: {$message}");

        $response = $chatService->processMessage($user, $message);

        if ($response) {
            $this->success("Ответ получен:");
            $this->line($response);
        } else {
            $this->error("Не удалось получить ответ");
            return 1;
        }

        return 0;
    }
}
