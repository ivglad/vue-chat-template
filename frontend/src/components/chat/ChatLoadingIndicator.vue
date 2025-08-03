<script setup>
// Простой индикатор загрузки для сообщений
</script>

<template>
  <div class="flex items-center gap-2 text-gray-500 text-sm">
    <div class="flex space-x-1">
      <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
      <div
        class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
        style="animation-delay: 0.1s"></div>
      <div
        class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
        style="animation-delay: 0.2s"></div>
    </div>
    <span>Печатает...</span>
  </div>
</template>
