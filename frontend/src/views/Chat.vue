<script setup></script>

<template>
  <div class="h-screen flex flex-col">
    <ChatContainer />
  </div>
</template>

<style scoped>
@import '@/assets/styles/main.css';

@media (prefers-reduced-motion: reduce) {
  :deep(*) {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
