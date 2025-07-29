<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateApiTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-test-user {--email=api@test.com} {--password=password123} {--name=API Test User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать тестового пользователя для API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Проверяем, существует ли пользователь
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            $this->error("Пользователь с email {$email} уже существует!");
            return 1;
        }

        // Создаем пользователя
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Тестовый пользователь создан успешно!");
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");
        $this->line("Name: {$name}");
        $this->line("ID: {$user->id}");
        
        $this->newLine();
        $this->line("Теперь вы можете использовать эти учетные данные для тестирования API:");
        $this->line("curl -X POST http://localhost/api/v1/auth/login \\");
        $this->line("  -H \"Content-Type: application/json\" \\");
        $this->line("  -d '{\"email\":\"{$email}\",\"password\":\"{$password}\"}'");

        return 0;
    }
}
