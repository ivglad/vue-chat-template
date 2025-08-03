<script setup>
const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div class="flex items-center justify-between h-16 px-4">
    <div class="flex items-center justify-center flex-1">
      <div>
        <h1 class="font-semibold text-gray-900">Docwise+</h1>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <div
        v-if="isLoading"
        class="flex items-center gap-2 text-sm text-gray-500">
        <ProgressSpinner style="width: 16px; height: 16px" stroke-width="4" />
        <span class="hidden sm:inline">Обработка...</span>
      </div>
    </div>
  </div>
</template>
