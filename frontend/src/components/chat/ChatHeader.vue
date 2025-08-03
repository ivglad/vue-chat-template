<script setup>
const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div class="flex items-center justify-between h-18 px-6">
    <div class="flex items-center justify-center flex-1">
      <div>
        <h1 class="font-semibold text-gray-900">Docwise+</h1>
      </div>
    </div>
  </div>
</template>
