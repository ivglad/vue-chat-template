<template>
  <div class="chat-page">
    <!-- Основной контейнер чата -->
    <ChatContainer />
    
    <!-- Глобальные уведомления -->
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useChatStore } from '@/stores/chat/useChatStore'
import ChatContainer from '@/components/chat/ChatContainer.vue'

/**
 * Рефакторенная страница чата
 * Использует новую архитектуру с композаблами и централизованным состоянием
 * Следует принципу Single Responsibility - только координация высокого уровня
 */

// ============================================================================
// Composables
// ============================================================================

const chatStore = useChatStore()

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(() => {
  // Инициализация чата при загрузке страницы
  // Дополнительная логика инициализации может быть добавлена здесь
})

// ============================================================================
// Meta
// ============================================================================

// Устанавливаем мета-информацию для страницы
defineOptions({
  name: 'ChatRefactored'
})
</script>

<style scoped>
.chat-page {
  @apply h-screen flex flex-col bg-surface-0;
}

/* Глобальные стили для чата */
:deep(.p-toast) {
  @apply z-50;
}

:deep(.p-toast .p-toast-message) {
  @apply rounded-xl shadow-lg;
}

:deep(.p-toast .p-toast-message-content) {
  @apply p-4;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-page {
    @apply h-screen;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  :deep(*) {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>