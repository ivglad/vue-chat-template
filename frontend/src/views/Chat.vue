<script setup>
const messagesHistory = ref([])
</script>

<template>
  <div class="flex flex-col flex-1">
    <ChatHeader />
    <ChatContent :messages-history="messagesHistory" />
    <ChatFooter />
  </div>
</template>

<style lang="scss" scoped></style>
