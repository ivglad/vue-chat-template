<script setup>
const date = ref(new Date())
const dates = ref()
const time = ref(new Date())

// Определение вариантов и состояний для выбора даты
const datePickerStates = ['Default', 'Disabled', 'Invalid']
const datePickerVariants = ['Default', 'Range']
</script>

<template>
  <LayoutUiTemplate
    title="DatePickers"
    :states="datePickerStates"
    :variants="datePickerVariants">
    <!-- Default -->
    <template #default-default>
      <DatePicker
        v-model="date"
        placeholder="Выберите дату"
        showButtonBar
        showIcon
        fluid
        iconDisplay="input"
        :manualInput="false" />
    </template>
    <template #default-disabled>
      <DatePicker
        v-model="date"
        placeholder="Выберите дату"
        showButtonBar
        showIcon
        iconDisplay="input"
        disabled
        :manualInput="false" />
    </template>
    <template #default-invalid>
      <DatePicker
        v-model="date"
        placeholder="Выберите дату"
        showButtonBar
        showIcon
        iconDisplay="input"
        :invalid="true"
        :manualInput="false" />
    </template>

    <!-- Range -->
    <template #range-default>
      <DatePicker
        v-model="dates"
        placeholder="Выберите дату"
        selectionMode="range"
        showIcon
        iconDisplay="input"
        showButtonBar
        :manualInput="false" />
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
