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
</script>

<template>
  <div class="w-full">
    <div
      v-if="message.type === 'user'"
      class="flex flex-col self-end space-y-2.5 max-w-[300px] p-4 bg-white rounded-2xl ml-auto">
      <ChatMessageDocuments
        v-if="message.context_documents?.length"
        :documents="message.context_documents" />
      <div>
        <ChatMessageContent
          :content="message.message"
          :type="message.type"
          :is-local="message.isLocal"
          :is-new="message.isNew"
          :message-id="message.id" />
      </div>
    </div>
    <div v-else-if="message.type === 'bot' && message.status !== 'error'">
      <ChatMessageContent
        :content="message.message"
        :type="message.type"
        :is-local="message.isLocal"
        :is-new="message.isNew"
        :message-id="message.id" />
    </div>
  </div>
</template>
