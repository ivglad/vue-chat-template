<script setup>
const menuEl = ref()
const settings = [
  {
    label: 'Опции',
    items: [
      {
        id: 0,
        label: 'Подробнее',
      },
      {
        id: 1,
        label: 'Уведомить',
      },
    ],
  },
]
const toggleMenu = (event) => {
  menuEl.value.toggle(event)
}
</script>

<template>
  <LayoutUiTemplate title="Card">
    <div class="content">
      <Card class="app-card">
        <template #header>
          <i-custom-favicon />
          <i-custom-logo />
        </template>
        <template #title class="fw-semibold">Заголовок</template>
        <template #subtitle>Подзаголовок</template>
        <template #content>
          <p class="m-0">
            Lorem ipsum dolor sit amet, consectetur adipisicing elit. Inventore
            sed consequuntur error repudiandae numquam deserunt quisquam
            repellat libero asperiores earum nam nobis, culpa ratione quam
            perferendis esse, cupiditate neque quas!
          </p>
        </template>
        <template #footer>
          <Button
            variant="text"
            severity="secondary"
            rounded
            @click="toggleMenu">
            <template #icon>
              <i-custom-options />
            </template>
          </Button>
          <Menu ref="menuEl" :model="settings" :popup="true">
            <template #item="{ item }">
              <div class="app-card-item">
                <i-custom-search v-if="item.id == 0" />
                <i-custom-notifications v-if="item.id == 1" />
                {{ item.label }}
              </div>
            </template>
          </Menu>
        </template>
      </Card>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped></style>
