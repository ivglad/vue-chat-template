<x-filament-panels::page>
    <div class="space-y-6">
        <!-- {{-- Заголовок и описание --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                            Обзор пользователей
                        </h3>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            Список всех пользователей, которые взаимодействовали с чат-ботом. 
                            Нажмите на "Открыть чат" для просмотра индивидуального чата пользователя.
                        </p>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- {{-- Статистика --}}
        @php
            $stats = app(\App\Services\ChatService::class)->getChatStatistics();
        @endphp
        
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Общие сообщения --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-chat-bubble-left-right class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Всего сообщений
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($stats['total_messages']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Активные пользователи --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-users class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Активных пользователей
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($stats['total_users']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Вопросы пользователей --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-question-mark-circle class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Вопросов задано
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($stats['user_messages']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ответы бота --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-cpu-chip class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Ответов дано
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($stats['bot_messages']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        {{-- Таблица пользователей --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page> 