<script setup>

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    default: 0,
  },
})

// Используем анимированные фразы загрузки
const { currentPhrase, startLoadingAnimation, stopLoadingAnimation } =
  useLoadingPhrases()

// Вычисляемые свойства для отображения состояний
const isLoadingMessage = computed(() => {
  return props.message.isLoading || props.message.status === 'loading'
})

const displayText = computed(() => {
  if (isLoadingMessage.value) {
    // Используем анимированные фразы для загрузки
    return currentPhrase.value
  }
  // Для сообщений об ошибке тоже используем loadingText
  if (props.message.status === 'error' && props.message.loadingText) {
    return props.message.loadingText
  }
  return props.message.message
})

const loadingTextClass = computed(() => {
  if (isLoadingMessage.value) {
    return ['animate-pulse']
  }
  return []
})

// Управление анимацией загрузочных фраз
watch(
  isLoadingMessage,
  (newValue) => {
    if (newValue) {
      startLoadingAnimation()
    } else {
      stopLoadingAnimation()
    }
  },
  { immediate: true },
)

// Очистка анимации при размонтировании компонента
onUnmounted(() => {
  stopLoadingAnimation()
})
</script>

<template>
  <div class="w-full">
    <div v-if="message.type === 'user'" class="flex justify-end">
      <div
        class="flex flex-col gap-2.5 max-w-[300px] p-4 bg-white rounded-2xl">
        <ChatMessageDocuments
          v-if="message.context_documents?.length"
          :documents="message.context_documents" />
        <div>
          <ChatMessageContent
            :content="displayText"
            :type="message.type"
            :is-local="message.isLocal"
            :is-new="message.isNew"
            :message-id="message.id" />
        </div>
      </div>
    </div>

    <div v-else>
      <div class="assistant-title flex items-center gap-2.5">
        <i-custom-robot-original class="w-[40px] h-[40px] flex-shrink-0" />
        <span class="italic" :class="loadingTextClass">
          {{
            isLoadingMessage
              ? currentPhrase
              : message.status === 'error'
              ? message.loadingText || 'Произошла ошибка :('
              : 'Вот что я нашёл по этому вопросу'
          }}
        </span>
      </div>
      <Divider
        v-if="!isLoadingMessage && message.status !== 'error'"
        class="assistant-divider-start bg-surface-400" />
      <div class="flex-1">
        <ChatMessageContent
          v-if="!isLoadingMessage && message.status !== 'error'"
          :content="message.message"
          :type="message.type"
          :is-local="message.isLocal"
          :is-new="message.isNew"
          :message-id="message.id" />
      </div>
    </div>
  </div>
</template>
