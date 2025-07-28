<script setup>
const messagesHistory = computed(() => {
  return [
    {
      id: 1,
      message: 'Кратко изложи регламент отдела логистики',
      type: 'user',
      context_documents: [{ id: 1, name: 'Регламент отдела логистики' }],
      created_at: '2023-12-01T10:00:00.000000Z',
      replies: [
        {
          id: 2,
          message:
            'Регламент отдела логистики включает в себя управление поставками, транспортировкой и складским хозяйством с целью оптимизации затрат и обеспечения своевременной доставки товаров. \nОтдел также отвечает за анализ эффективности процессов, взаимодействие с другими подразделениями и соблюдение нормативных требований',
          type: 'bot',
          context_documents: [{ id: 1, name: 'Регламент отдела логистики' }],
          created_at: '2023-12-01T10:00:05.000000Z',
        },
      ],
    },
  ]
})
</script>

<template>
  <div class="flex flex-col flex-1">
    <ChatHeader />
    <ChatContent :messages-history="messagesHistory" />
    <ChatFooter v-model:messages-history="messagesHistory" />
  </div>
</template>

<style lang="scss" scoped></style>
