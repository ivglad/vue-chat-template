<script setup>
import ChatMessageContent from './ChatMessageContent.vue'
import ChatMessageDocuments from './ChatMessageDocuments.vue'
import ChatLoadingIndicator from './ChatLoadingIndicator.vue'

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

const messageClasses = computed(() => {
  if (props.message.type === 'user') {
    return 'bg-white rounded-2xl p-4 max-w-[300px] ml-auto'
  } else {
    return 'flex items-start gap-2'
  }
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
      <div class="flex flex-col gap-2.5 max-w-[300px]">
        <!-- Документы выше сообщения -->
        <ChatMessageDocuments
          v-if="message.context_documents?.length"
          :documents="message.context_documents" />
        
        <!-- Само сообщение -->
        <div :class="messageClasses" :data-local="message.isLocal">
          <ChatMessageContent
            :content="message.message"
            :type="message.type"
            :is-local="message.isLocal" />
        </div>
      </div>
    </div>

    <!-- Сообщение ассистента -->
    <div v-else :class="messageClasses">
      <i-custom-robot-original class="w-[40px] h-[40px] flex-shrink-0" />
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

<style scoped>
[data-local='true'] {
  opacity: 0.8;
  background-color: #f3f4f6;
}
</style>
