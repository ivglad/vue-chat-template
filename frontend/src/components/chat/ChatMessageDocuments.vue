<script setup>
const props = defineProps({
  documents: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div v-if="documents.length" class="space-y-2">
    <div
      v-for="document in documents"
      :key="document"
      class="flex items-center gap-2.5 w-full p-3 rounded-xl bg-[#EDEFF6]">
      <i-custom-doc class="text-primary flex-shrink-0" />
      <div class="flex-1 min-w-0">
        <div class="truncate">
          {{ document }}
        </div>
      </div>
    </div>
  </div>
</template>
