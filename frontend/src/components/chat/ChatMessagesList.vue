<script setup>
import ChatMessage from './ChatMessage.vue'
import { useContainerOverflow } from '@/composables/useContainerOverflow'

const props = defineProps({
  messages: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  hasMessages: {
    type: Boolean,
    default: false,
  },
})

// Убираем emit для retry-message, так как функциональность повтора не нужна

const messagesContainer = ref(null)
const messagesChildContainer = ref(null)

// Используем композабл для отслеживания переполнения
const { containerClasses } = useContainerOverflow(
  messagesContainer,
  messagesChildContainer,
  {
    overflowClass: 'pr-1',
    compareBy: 'height',
    watchProps: [() => props.messages.length],
  },
)

// Обработать скролл контейнера сообщений
const handleScroll = () => {
  // Логика скролла может быть добавлена здесь при необходимости
}

defineExpose({
  messagesContainer,
})
</script>

<template>
  <div
    ref="messagesContainer"
    class="flex-1 items w-full overflow-y-auto px-6 py-6 space-y-4 scroll-smooth"
    :class="containerClasses"
    @scroll="handleScroll">
    <ChatEmptyState v-if="!hasMessages && !isLoading" />

    <div v-else ref="messagesChildContainer" class="space-y-4">
      <ChatMessage
        v-for="(message, index) in messages"
        :key="message.id"
        :message="message"
        :index="index"
        class="max-w-[70rem] justify-self-center" />
      <Divider class="bg-surface-400" />
    </div>

    <div
      v-if="isLoading && !hasMessages"
      class="flex items-center justify-center py-8">
      <ProgressSpinner style="width: 32px; height: 32px" stroke-width="3" />
    </div>
  </div>
</template>
