<script setup>

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

defineExpose({
  messagesContainer,
})
</script>

<template>
  <div
    ref="messagesContainer"
    class="flex-1 items w-full overflow-y-auto px-6 py-4 pb-0 space-y-4 scroll-smooth"
    :class="containerClasses">
    <ChatEmptyState v-if="!hasMessages && !isLoading" />

    <div v-else ref="messagesChildContainer" class="space-y-4">
      <ChatMessage
        v-for="(message, index) in messages"
        :key="message.id"
        :message="message"
        :index="index"
        class="max-w-[70rem] justify-self-center" />
      <Divider class="assistant-divider-end bg-surface-400" />
    </div>

    <div
      v-if="isLoading && !hasMessages"
      class="flex items-center justify-center py-8">
      <ProgressSpinner style="width: 32px; height: 32px" stroke-width="3" />
    </div>
  </div>
</template>
