<script setup></script>

<template>
  <div class="flex w-full items-center justify-center h-[70px] p-6">
    <Button variant="text" rounded>
      <template #icon>
        <i-custom-menu class="text-surface-500" />
      </template>
    </Button>
    <div class="flex w-full justify-center font-semibold text-base">
      Docwise+
    </div>
    <Button variant="text" rounded>
      <template #icon>
        <i-custom-filter class="text-surface-500" />
      </template>
    </Button>
  </div>
</template>
