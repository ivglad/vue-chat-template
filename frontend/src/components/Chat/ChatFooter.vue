<script setup>
import { motion, AnimatePresence } from 'motion-v'
import { onClickOutside } from '@vueuse/core'

const messagesHistory = defineModel('messagesHistory')
console.log(messagesHistory.value)

const message = ref('')

const documentsMenu = ref()
const documentsMenuRef = ref()
const documents = ref([
  { label: 'Регламенты компании' },
  { label: 'Документы сотрудников' },
  { label: 'Документы клиентов' },
  { label: 'Документы партнеров' },
  { label: 'Документы сотрудников 1' },
  { label: 'Правила ведения документации' },
  { label: 'Регламенты для управления документацией' },
  { label: 'Регламенты для управления документацией 1' },
])
const selectedDocument = ref(null)

// Закрытие меню при клике вне области
onClickOutside(documentsMenuRef, () => {
  documentsMenu.value = false
})
</script>

<template>
  <div class="flex w-full items-center justify-center relative px-6 py-4">
    <AnimatePresence>
      <motion.div
        v-if="documentsMenu"
        ref="documentsMenuRef"
        :initial="{ opacity: 0, y: 10, scale: 0.95 }"
        :animate="{ opacity: 1, y: 0, scale: 1 }"
        :exit="{ opacity: 0, y: 10, scale: 0.95 }"
        :transition="{
          duration: 0.15,
          ease: 'easeOut',
        }"
        class="absolute bottom-full left-6 z-10">
        <Listbox
          v-model="selectedDocument"
          :options="documents"
          optionLabel="label"
          class="max-w-[250px] border-none shadow-none rounded-2xl"
          :pt="{
            list: 'pl-2.5 pr-0 overflow-hidden',
            option: 'block rounded-xl text-nowrap text-ellipsis',
          }"
          @change="documentsMenu = false" />
      </motion.div>
    </AnimatePresence>

    <motion.div
      layout
      :transition="{ duration: 0.3, ease: 'easeOut' }"
      class="flex flex-col w-full items-center justify-center gap-2.5 bg-surface-0 min-h-[54px] rounded-2xl p-2">
      <AnimatePresence>
        <motion.div
          v-if="selectedDocument"
          layout
          :initial="{ opacity: 0, scale: 0.98 }"
          :animate="{ opacity: 1, scale: 1 }"
          :exit="{ opacity: 0, scale: 0.98 }"
          :transition="{
            duration: 0.2,
            ease: 'easeOut',
          }"
          class="flex items-center gap-2.5 w-full p-3 rounded-xl bg-[#EDEFF6]">
          <i-custom-doc class="text-primary" />
          <div>
            {{ selectedDocument?.label }}
          </div>
          <Button
            class="p-0 ml-auto"
            variant="text"
            @click="selectedDocument = null">
            <template #icon>
              <i-custom-cross />
            </template>
          </Button>
        </motion.div>
      </AnimatePresence>

      <div class="flex w-full items-center justify-center">
        <Button
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="plus"
          outlined
          aria-haspopup="true"
          aria-controls="documents-menu"
          @click="documentsMenu = !documentsMenu">
          <template #icon>
            <i-custom-plus />
          </template>
        </Button>
        <Textarea
          v-model="message"
          id="message-textarea"
          class="h-full px-2 py-2 border-none shadow-none"
          placeholder="Задайте вопрос..."
          rows="1" />
        <Button
          :disabled="!message"
          class="w-[38px] min-w-[38px] h-[38px] min-h-[38px] rounded-xl"
          aria-label="plus">
          <template #icon>
            <i-custom-send />
          </template>
        </Button>
      </div>
    </motion.div>
  </div>
</template>
