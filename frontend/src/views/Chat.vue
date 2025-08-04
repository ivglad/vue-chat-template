<script setup>
defineOptions({
  name: 'ChatRefactored',
})
</script>

<template>
  <div class="h-screen flex flex-col bg-white">
    <ChatContainer />
  </div>
</template>

<style scoped>
@import '@/assets/styles/main.css';

@media (max-width: 768px) {
  .h-screen {
    height: 100vh;
  }
}

@media (prefers-reduced-motion: reduce) {
  :deep(*) {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
