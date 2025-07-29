<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Статус бота -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Текущий статус</h2>
            
            @php
                $status = $this->getBotStatusForView();
                $isActive = $status['active'];
                $processExists = $status['process_exists'];
                $processId = $status['process_id'];
                $startedAt = $status['started_at'];
                $lastHeartbeat = $status['last_heartbeat'];
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Основной статус -->
                <div class="flex items-center space-x-3">
                    @if($isActive)
                        <div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-400">Активен</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Бот работает</p>
                        </div>
                    @else
                        <div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-400">Неактивен</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Бот остановлен</p>
                        </div>
                    @endif
                </div>
                
                <!-- PID процесса -->
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">PID процесса</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if($processId)
                            {{ $processId }}
                            @if($processExists)
                                <span class="text-green-600 dark:text-green-400">✓</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">✗</span>
                            @endif
                        @else
                            —
                        @endif
                    </p>
                </div>
                
                <!-- Время запуска -->
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Время запуска</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if($startedAt)
                            {{ $startedAt->format('d.m.Y H:i') }}
                            <br>
                            <span class="text-xs">{{ $startedAt->diffForHumans() }}</span>
                        @else
                            —
                        @endif
                    </p>
                </div>
                
                <!-- Последний ответ -->
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Последний ответ</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if($lastHeartbeat)
                            {{ $lastHeartbeat->format('d.m.Y H:i:s') }}
                            <br>
                            <span class="text-xs">{{ $lastHeartbeat->diffForHumans() }}</span>
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Автообновление каждые 10 секунд
        setInterval(function() {
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('checkBotStatus');
            }
        }, 10000);
    </script>
</x-filament-panels::page> 