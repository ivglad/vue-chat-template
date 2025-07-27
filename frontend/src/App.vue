<script setup>
const isDarkMode = computed(() => {
  return document.body.classList.contains('p-dark-mode')
})
provide('isDarkMode', isDarkMode)
</script>

<template>
  <Toast>
    <template #closeicon>
      <!-- <i-custom-close /> -->
    </template>
    <template #container="{ message, closeCallback }">
      <div class="app-toast p-toast-message-content">
        <div v-if="message.summary" class="app-toast-summary">
          <!-- <i-custom-success-sign v-if="message.severity === 'success'" />
          <i-custom-info-sign v-else-if="message.severity === 'info'" />
          <i-custom-warning-sign v-else-if="message.severity === 'warn'" />
          <i-custom-error-sign v-else-if="message.severity === 'error'" /> -->
          <span class="p-toast-summary fw-bold">{{ message.summary }}</span>
          <Button
            class="app-toast-close-button"
            variant="text"
            size="small"
            severity="secondary"
            rounded
            @click="closeCallback">
            <template #icon>
              <!-- <i-custom-plus class="icon-plus--transform" /> -->
            </template>
          </Button>
        </div>
        <div v-if="message.detail" class="app-toast-detail p-toast-detail">
          {{ message.detail }}
        </div>
      </div>
    </template>
  </Toast>

  <main>
    <router-view />
  </main>

  <DynamicDialog />
  <ConfirmDialog group="confirm" pt:root:class="app-confirm-modified" />
  <ConfirmDialog
    group="confirm-secondary"
    pt:root:class="app-confirm-secondary" />
  <ConfirmPopup />
</template>

<style lang="scss">
#app {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  min-width: 100vw;
  min-height: 100vh;
}

main {
  display: flex;
  flex-direction: column;
  flex: 1 1 100%;
  width: 100%;
  overflow: auto;
}
</style>
