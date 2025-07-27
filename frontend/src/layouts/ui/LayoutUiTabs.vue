<script setup>
const tabs = ref([
  {
    id: 'all',
    title: 'Все',
    content: 'Tab 1 Content',
    value: 0,
    badgeValue: '199',
  },
  {
    id: 'draft',
    title: 'Черновики',
    content: 'Tab 2 Content',
    value: 1,
    badgeValue: '23',
  },
  {
    id: 'for-approval',
    title: 'На согласовании',
    content: 'Tab 3 Content',
    value: 2,
    badgeValue: '11',
  },
  {
    id: 'approved',
    title: 'Согласовано',
    content: 'Tab 4 Content',
    value: 3,
    badgeValue: '1',
  },
  {
    id: 'active',
    title: 'Действующие',
    content: 'Tab 5 Content',
    value: 4,
    badgeValue: '9',
  },
  {
    id: 'inactive',
    title: 'Не действующие',
    content: 'Tab 6 Content',
    value: 5,
    badgeValue: '16',
  },
  {
    id: 'rejected',
    title: 'Отклонены',
    content: 'Tab 7 Content',
    value: 6,
    badgeValue: '14',
  },
])
</script>

<template>
  <LayoutUiTemplate title="Tabs">
    <div class="content">
      <Tabs :value="0" scrollable>
        <TabList>
          <Tab
            class="tab"
            :class="`tab-${tab.id}`"
            v-for="(tab, index) in tabs"
            :key="tab.title"
            :value="tab.value"
            :disabled="index === 2">
            {{ tab.title }}
            <Badge :value="tab.badgeValue"></Badge>
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="tab in tabs" :key="tab.content" :value="tab.value">
            <span>{{ tab.title }}</span>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.tab {
  &-draft {
    &:not(:hover):not(:active) {
      color: var(--p-stone-500);
    }
    .p-badge {
      background: var(--p-stone-500);
    }
  }
}
</style>
