<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>
    
    <script>
        // Автообновление статуса каждые 10 секунд
        setInterval(function() {
            if (typeof Livewire !== 'undefined') {
                // Обновляем статус бота
                @this.call('checkBotStatus');
                @this.call('refreshBotStatusFields');
            }
        }, 10000);
    </script>
</x-filament-panels::page>