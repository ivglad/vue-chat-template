<script setup>
import { motion } from 'motion-v'

const router = useRouter()

// Композабл для поочередной анимации элементов
const { getElementAnimationProps } = usePageTransition({
  staggerDelay: 0.15,
  enterDuration: 0.4,
  enterDelay: 0.2,
})

const goHome = () => {
  router.push('/')
}

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    goHome()
  }
}

defineOptions({
  name: 'ErrorPage',
})
</script>

<template>
  <div
    class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4">
    <motion.div
      v-bind="getElementAnimationProps(0)"
      class="text-center max-w-md w-full">
      <motion.div
        v-bind="getElementAnimationProps(1)"
        class="text-8xl md:text-9xl font-black text-primary-500 mb-4 select-none">
        404
      </motion.div>

      <motion.h1
        v-bind="getElementAnimationProps(2)"
        class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
        Упс! Страница не найдена
      </motion.h1>

      <motion.p
        v-bind="getElementAnimationProps(3)"
        class="text-gray-600 text-lg leading-relaxed mb-8">
        Страница, которую вы ищете, была удалена, переименована или временно
        недоступна.
      </motion.p>

      <motion.div
        v-bind="getElementAnimationProps(4)"
        class="flex flex-col sm:flex-row gap-4 justify-center">
        <Button
          label="На главную"
          icon="pi pi-home"
          class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-3 rounded-xl font-medium transition-colors duration-200"
          @click="goHome" />

        <Button
          label="Назад"
          icon="pi pi-arrow-left"
          outlined
          class="border-gray-300 text-gray-700 hover:bg-gray-50 px-6 py-3 rounded-xl font-medium transition-colors duration-200"
          @click="goBack" />
      </motion.div>
    </motion.div>

    <motion.div
      v-bind="getElementAnimationProps(5)"
      class="absolute top-10 left-10 w-20 h-20 bg-primary-100 rounded-full opacity-50 blur-xl" />
    <motion.div
      v-bind="getElementAnimationProps(6)"
      class="absolute bottom-10 right-10 w-32 h-32 bg-gray-200 rounded-full opacity-30 blur-2xl" />
    <motion.div
      v-bind="getElementAnimationProps(7)"
      class="absolute top-1/2 left-1/4 w-16 h-16 bg-primary-200 rounded-full opacity-40 blur-lg" />
  </div>
</template>
