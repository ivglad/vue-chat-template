<script setup>
const radioState = ref('')

// Определение вариантов и состояний для радиокнопок
const radioStates = ['Default', 'Disabled']
const radioVariants = ['Large', 'Default', 'Small']
</script>

<template>
  <LayoutUiTemplate
    title="Radios"
    :states="radioStates"
    :variants="radioVariants">
    <!-- Large -->
    <template #large-default>
      <RadioButton
        v-model="radioState"
        value="Radio 1"
        name="radios"
        size="large" />
    </template>
    <template #large-disabled>
      <RadioButton
        v-model="radioState"
        value="Radio 1"
        name="radios"
        size="large"
        disabled />
    </template>

    <!-- Default -->
    <template #default-default>
      <label class="app-radio">
        <RadioButton v-model="radioState" value="Radio 2" name="radios" />
        <Message class="app-radio-message" variant="simple" severity="contrast">
          Radio
        </Message>
      </label>
    </template>
    <template #default-disabled>
      <label class="app-radio">
        <RadioButton
          v-model="radioState"
          value="Radio 2"
          name="radios"
          disabled />
        <Message class="app-radio-message" variant="simple" severity="contrast">
          Radio
        </Message>
      </label>
    </template>

    <!-- Small -->
    <template #small-default>
      <RadioButton
        v-model="radioState"
        value="Radio 3"
        name="radios"
        size="small" />
    </template>
    <template #small-disabled>
      <RadioButton
        v-model="radioState"
        value="Radio 3"
        name="radios"
        size="small"
        disabled />
    </template>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
