<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем роли
        $adminRole = Role::firstOrCreate(['name' => 'Aдминистратор']);
        $userRole = Role::firstOrCreate(['name' => 'Пользователь']);

        // Создаем разрешения (если нужно)
        $permissions = [
            'просмотр документов',
            'загрузка документов',
            'удаление документов',
            'управление пользователями',
            'управление системой',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Назначаем разрешения ролям
        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions([
            'просмотр документов',
        ]);
    }
}