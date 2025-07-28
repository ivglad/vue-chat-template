<script setup>
const props = defineProps({
  messagesHistory: {
    type: Array,
    required: true,
  },
})

const messages = computed(() => {
  if (props.messagesHistory.length) return [...props.messagesHistory]
  return []
})
</script>

<template>
  <div class="flex flex-1 flex-col h-full px-6 py-4">
    <div
      v-if="!messages.length"
      class="flex flex-col items-center justify-center flex-1">
      <i-custom-robot-original class="w-24 h-24" />
      <span class="text-xl">Чем я могу помочь?</span>
    </div>
    <div v-else class="flex flex-col gap-2.5">
      <div
        v-for="message in messages"
        :key="message.id"
        class="flex flex-col gap-4">
        <div class="flex justify-end">
          <div
            class="flex flex-col max-w-[300px] bg-surface-0 rounded-2xl p-4 gap-2.5">
            <div
              v-for="document in message.context_documents"
              :key="document.id"
              class="flex items-center gap-2.5 w-full h-14 p-3 rounded-xl bg-[#EDEFF6]">
              <i-custom-doc class="text-primary" />
              <div>
                {{ document.name }}
              </div>
            </div>
            <div>
              {{ message.message }}
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 py-3 relative">
          <i-custom-robot-original class="w-[40px] h-[40px]" />
          <span class="italic">Вот что я нашел по этому вопросу</span>
          <Divider
            type="solid"
            class="absolute bottom-0 left-0 m-0 before:bg-[#CFCFDB]" />
        </div>
        <div
          v-for="replies in message.replies"
          :key="replies.id"
          class="flex items-center gap-2.5 pb-4 relative">
          <div class="text-sm text-text-secondary whitespace-pre-line">
            {{ replies.message }}
          </div>
          <Divider
            type="solid"
            class="absolute bottom-0 left-0 m-0 before:bg-[#CFCFDB]" />
        </div>
      </div>
    </div>
  </div>
</template>
