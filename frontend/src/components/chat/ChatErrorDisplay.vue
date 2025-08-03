<script setup>
const props = defineProps({
  error: {
    type: Object,
    required: true,
  },
  canRetry: {
    type: Function,
    required: true,
  },
})

const emit = defineEmits(['retry', 'clear'])

const handleRetry = () => {
  emit('retry')
}

const handleClear = () => {
  emit('clear')
}
</script>

<template>
  <div class="p-4 bg-red-50 border border-red-200 rounded-lg m-4">
    <div class="flex items-center justify-between">
      <span class="text-red-800">{{ error.message }}</span>
      <div class="flex gap-2">
        <Button v-if="canRetry(error)" size="small" @click="handleRetry">
          Повторить
        </Button>
        <Button size="small" text @click="handleClear"> Закрыть </Button>
      </div>
    </div>
  </div>
</template>
