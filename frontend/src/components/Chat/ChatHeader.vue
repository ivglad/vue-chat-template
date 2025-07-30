<script setup></script>

<template>
  <div class="flex w-full items-center justify-center h-[70px] p-6">
    <Button class="invisible text-surface-500" variant="text" rounded>
      <template #icon>
        <i-custom-menu />
      </template>
    </Button>
    <div class="flex w-full justify-center font-semibold text-base">
      Docwise+
    </div>
    <Button class="invisible not-first:text-surface-500" variant="text" rounded>
      <template #icon>
        <i-custom-filter />
      </template>
    </Button>
  </div>
</template>
