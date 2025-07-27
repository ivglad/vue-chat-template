<script setup>
const customersVisits = () => {
  return Array.from({ length: 5 }).map((_, i) => ({
    id: i + 1,
    visit: `Визит ${i + 1}`,
    date: new Date().toLocaleDateString(),
  }))
}
const customers = ref(
  Array.from({ length: 100 }).map((_, i) => ({
    id: i + 1,
    name: `Имя ${i + 1}`,
    lastName: `Фамилия ${i + 1}`,
    age: Math.floor(Math.random() * 100),
    visits: Math.floor(Math.random() * 100),
    visitsData: customersVisits(),
    status:
      Math.floor(Math.random() * 100) % 2 === 0 ? 'Успешно' : 'Есть вопросы',
    progress: Math.floor(Math.random() * 100),
  })),
)

const totalVisits = computed(() =>
  customers.value.reduce((acc, row) => acc + row.visits, 0),
)

const totalSelectedRows = ref(null)
const selectedCustomer = ref(null)
const selectedCustomerExpandedRows = ref({})
const onSelectedRows = (rows) => {
  totalSelectedRows.value = rows?.length ?? 0
}

const paginatorDropdownStyle = {
  pcRowPerPageDropdown: {
    root: {
      class: 'p-select-sm p-inputfield-sm ml-10',
    },
    overlay: {
      class: 'p-select-overlay-sm',
    },
  },
}
</script>

<template>
  <LayoutUiTemplate title="Table">
    <div class="content">
      <div class="table">
        <DataTable
          v-model:selection="selectedCustomer"
          v-model:expandedRows="selectedCustomerExpandedRows"
          :value="customers"
          selectionMode="multiple"
          rowHover
          dataKey="id"
          :rows="10"
          :rowsPerPageOptions="[10, 20, 50]"
          paginator
          sortMode="multiple"
          removableSort
          highlightOnSelect
          stripedRows
          stateStorage="session"
          stateKey="layout-ui-table"
          @update:selection="onSelectedRows"
          :pt:pcPaginator="paginatorDropdownStyle">
          <ColumnGroup type="header">
            <Row>
              <Column />
              <Column />
              <Column header="Пользователь" :colspan="2" />
              <Column header="Информация" :colspan="4" />
            </Row>
            <Row>
              <Column />
              <Column selectionMode="multiple" headerStyle="width: 3rem" />
              <Column field="name" header="Имя" sortable />
              <Column field="lastName" header="Фамилия" sortable />
              <Column field="age" header="Возраст" sortable />
              <Column field="visits" header="Посещений" sortable />
              <Column field="status" header="Статус" sortable />
              <Column field="progress" header="Прогресс" sortable />
            </Row>
          </ColumnGroup>
          <Column expander style="width: 5rem">
            <template #rowtoggleicon="{ rowExpanded }">
              <i-custom-arrow-right
                :style="rowExpanded ? 'transform: rotate(90deg);' : ''" />
            </template>
          </Column>
          <Column selectionMode="multiple" />
          <Column field="name" />
          <Column field="lastName" />
          <Column field="age" />
          <Column field="visits" />
          <Column field="status" />
          <Column field="progress" />
          <ColumnGroup type="footer">
            <Row>
              <Column footer="Всего:" :colspan="5" />
              <Column :footer="totalVisits" :colspan="3" />
            </Row>
            <Row>
              <Column footer="Выбрано строк:" :colspan="2" />
              <Column :footer="totalSelectedRows" :colspan="6" />
            </Row>
          </ColumnGroup>
          <template #expansion="slotProps">
            <div class="ml-30 mtb-10">
              <div class="ml-10">
                Посещения пользователя:
                <span class="fw-semibold"
                  >{{ slotProps.data.name }} {{ slotProps.data.lastName }}</span
                >
              </div>
              <DataTable
                :value="slotProps.data.visitsData"
                removableSort
                style="width: fit-content">
                <Column field="visit" header="Визит" sortable></Column>
                <Column field="date" header="Дата" sortable></Column>
              </DataTable>
            </div>
          </template>
        </DataTable>
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.table {
  display: flex;
  flex-direction: column;
  gap: 0;
  overflow-x: auto;
  overflow-y: hidden;
  :deep(table) {
    width: 100%;
    padding-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    thead {
      color: var(--p-surface-500);
      background: var(--p-surface-100);
      tr {
        height: 3rem;
        &:first-child {
          th {
            &:first-child {
              border-radius: 10px 0 0 0;
            }
            &:last-child {
              border-radius: 0 10px 0 0;
            }
          }
        }
        &:nth-child(2) {
          th {
            text-align: left;
          }
        }
        th {
          padding: 1rem 2rem 1rem 1rem;
          .th-title {
            position: relative;
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            width: fit-content;
            margin-right: 2rem;
            .icon {
              position: absolute;
              left: calc(100% + 0.5rem);
              transition: none;
            }
          }
        }
      }
    }
    tbody {
      tr {
        @include transition;
      }
      .row {
        &-selected {
          background: var(--p-primary-100);
        }
        &-disabled {
          opacity: 0.4;
          filter: grayscale(0.9);
          user-select: none;
          pointer-events: none;
        }
      }
    }
  }
}
</style>
