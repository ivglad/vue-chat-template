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
  showLoading: {
    type: Boolean,
    default: false,
  },
})

const animationPreset = computed(() => {
  if (props.message.isLocal) {
    return 'messageSlideIn'
  }
  return 'messageAppear'
})

const animationDelay = computed(() => {
  return props.index * 0.05
})
</script>

<template>
  <AnimatedContainer
    :preset="animationPreset"
    :delay="animationDelay"
    container-class="w-full"
    :data-message-id="message.id">
    <!-- Сообщение пользователя -->
    <div v-if="message.type === 'user'" class="flex justify-end">
      <div class="flex flex-col gap-2.5 max-w-[300px] p-4 bg-white rounded-2xl">
        <!-- Документы выше сообщения -->
        <ChatMessageDocuments
          v-if="message.context_documents?.length"
          :documents="message.context_documents" />

        <!-- Само сообщение -->
        <div>
          <ChatMessageContent
            :content="message.message"
            :type="message.type"
            :is-local="message.isLocal" />
        </div>
      </div>
    </div>

    <!-- Сообщение ассистента -->
    <div v-else>
      <div class="flex items-center gap-2.5">
        <i-custom-robot-original class="w-[40px] h-[40px] flex-shrink-0" />
        <span class="italic">Вот что я нашёл по этому вопросу</span>
      </div>
      <Divider class="bg-surface-400" />
      <div class="flex-1">
        <ChatMessageContent
          :content="message.message"
          :type="message.type"
          :is-local="message.isLocal" />
        <ChatLoadingIndicator v-if="showLoading" class="mt-2" />
      </div>
    </div>
  </AnimatedContainer>
</template>
