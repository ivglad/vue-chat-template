<script setup>
const message = ref('')

const documentsMenu = ref()
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
</script>

<template>
  <div class="flex w-full items-center justify-center relative px-6 py-4">
    <Listbox
      v-if="documentsMenu"
      v-model="selectedDocument"
      :options="documents"
      optionLabel="label"
      class="max-w-[250px] absolute bottom-full left-6 border-none shadow-none rounded-2xl"
      @change="documentsMenu = null" />
    <div
      class="flex flex-col w-full items-center justify-center gap-2.5 bg-surface-0 min-h-[54px] rounded-2xl p-2">
      <div
        v-if="selectedDocument"
        class="flex items-center gap-2.5 w-full h-14 p-3 rounded-xl bg-[#EDEFF6]">
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
      </div>

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
    </div>
  </div>
</template>
