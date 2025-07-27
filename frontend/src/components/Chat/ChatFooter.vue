<script setup>
const message = ref('')
</script>

<template>
  <div class="flex w-full items-center justify-center px-6 py-4">
    <div
      class="flex w-full items-center justify-center bg-surface-0 h-[54px] rounded-2xl p-2">
      <Button
        class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
        aria-label="plus"
        outlined>
        <template #icon>
          <i-custom-plus />
        </template>
      </Button>
      <Textarea
        id="textarea-label-state"
        class="p-2 border-none shadow-none"
        v-model="message"
        rows="1" />
      <Button
        class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
        aria-label="plus">
        <template #icon>
          <i-custom-send />
        </template>
      </Button>
    </div>
  </div>
</template>
