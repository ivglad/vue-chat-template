<script setup>
import ChatMessage from './ChatMessage.vue'

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
  isLoadingForMessage: {
    type: Function,
    required: true,
  },
})

const messagesContainer = ref(null)

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
    class="flex-1 overflow-y-auto px-4 py-6 space-y-4 scroll-smooth"
    @scroll="handleScroll">
    <ChatEmptyState v-if="!hasMessages && !isLoading" />

    <AnimatedList
      v-else
      :items="messages"
      item-preset="messageAppear"
      :stagger-delay="0.05"
      container-class="space-y-4"
      :key-extractor="(msg) => msg.id">
      <template #item="{ item: message, index }">
        <ChatMessage
          :message="message"
          :index="index"
          :show-loading="isLoadingForMessage(message.id)" />
      </template>
    </AnimatedList>

    <div v-if="isLoading && !hasMessages" class="flex justify-center py-8">
      <ProgressSpinner style="width: 32px; height: 32px" stroke-width="3" />
    </div>
  </div>
</template>
