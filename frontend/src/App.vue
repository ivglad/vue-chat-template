<script setup>
import { AnimatePresence, motion } from 'motion-v'

const isDarkMode = computed(() => {
  return document.body.classList.contains('p-dark-mode')
})
provide('isDarkMode', isDarkMode)

// Инициализируем viewport fallback только для старых браузеров
const { initViewportFallback, cleanupViewportFallback } = useViewportFallback()

onMounted(() => {
  initViewportFallback()
})

onUnmounted(() => {
  cleanupViewportFallback()
})

// Используем централизованную систему анимаций
const { createAnimationProps } = useChatAnimations()

// Управление переходами между страницами
const router = useRouter()
const transitionType = ref('fade')

// Определяем тип анимации на основе перехода
router.beforeEach((to, from) => {
  if (from.path === '/auth' && to.path === '/chat') {
    transitionType.value = 'auth-to-chat'
  } else if (from.path === '/chat' && to.path === '/auth') {
    transitionType.value = 'chat-to-auth'
  } else {
    transitionType.value = 'fade'
  }
})

// Получаем анимационные пропсы из централизованной системы
const pageAnimationProps = computed(() => {
  const presetMap = {
    fade: 'pageTransitionFade',
    'auth-to-chat': 'pageTransitionAuthToChat',
    'chat-to-auth': 'pageTransitionChatToAuth',
  }
  
  const presetName = presetMap[transitionType.value] || presetMap.fade
  return createAnimationProps(presetName)
})
</script>

<template>
  <Toast>
    <template #closeicon>
      <i-custom-cross />
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
              <!-- <i-custom-cross class="text-color" /> -->
            </template>
          </Button>
        </div>
        <div
          v-if="message.detail"
          class="app-toast-detail p-toast-detail text-color">
          {{ message.detail }}
        </div>
      </div>
    </template>
  </Toast>

  <main>
    <router-view v-slot="{ Component, route }">
      <AnimatePresence mode="wait">
        <motion.div
          :key="route.path"
          v-bind="pageAnimationProps"
          class="w-full h-full overflow-hidden">
          <component :is="Component" />
        </motion.div>
      </AnimatePresence>
    </router-view>
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
  width: 100%;
  height: 100%;
  min-width: 100vw;
  min-height: var(--viewport-height);
}

main {
  display: flex;
  flex-direction: column;
  flex: 1 1 100%;
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
}
</style>
