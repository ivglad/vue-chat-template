<script setup>
import { AnimatePresence, motion } from 'motion-v'

const isDarkMode = computed(() => {
  return document.body.classList.contains('p-dark-mode')
})
provide('isDarkMode', isDarkMode)

// Управление переходами между страницами
const route = useRoute()
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

// Варианты анимации для motion
const pageVariants = {
  // Базовая fade анимация
  fade: {
    initial: { opacity: 0, scale: 0.95, filter: 'blur(4px)' },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)' },
    exit: { opacity: 0, scale: 0.95, filter: 'blur(4px)' },
  },
  // Появление для перехода auth -> chat
  'auth-to-chat': {
    initial: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: 20 },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)', y: 0 },
    exit: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: -20 },
  },
  // Появление для перехода chat -> auth
  'chat-to-auth': {
    initial: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: 20 },
    animate: { opacity: 1, scale: 1, filter: 'blur(0px)', y: 0 },
    exit: { opacity: 0, scale: 0.9, filter: 'blur(6px)', y: -20 },
  },
}

// Настройки перехода для разных типов
const pageTransition = computed(() => {
  const transitions = {
    fade: {
      duration: 0.3,
      ease: [0.25, 0.46, 0.45, 0.94],
    },
    'auth-to-chat': {
      duration: 0.4,
      ease: [0.23, 1, 0.32, 1],
    },
    'chat-to-auth': {
      duration: 0.4,
      ease: [0.23, 1, 0.32, 1],
    },
  }
  return transitions[transitionType.value] || transitions.fade
})
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
    <router-view v-slot="{ Component, route }">
      <AnimatePresence mode="wait">
        <motion.div
          :key="route.path"
          :initial="pageVariants[transitionType].initial"
          :animate="pageVariants[transitionType].animate"
          :exit="pageVariants[transitionType].exit"
          :transition="pageTransition"
          class="w-full h-full flex-1">
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
  min-width: 100vw;
  min-height: 100vh;
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
