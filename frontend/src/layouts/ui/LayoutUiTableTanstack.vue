<script setup>
import { FlexRender } from '@tanstack/vue-table'

const columnHelper = createColumnHelper()
const columns = ref([
  {
    id: 'select',
    header: ({ table }) => {
      return h(Checkbox, {
        modelValue: table.getIsAllRowsSelected(),
        indeterminate: table.getIsSomeRowsSelected(),
        onChange: table.getToggleAllRowsSelectedHandler(),
        binary: true,
        size: 'big',
      })
    },
    cell: ({ row }) => {
      return h(Checkbox, {
        modelValue: row.getIsSelected(),
        onChange: row.getToggleSelectedHandler(),
        disabled: !row.getCanSelect(),
        binary: true,
        size: 'big',
      })
    },
  },
  columnHelper.group({
    header: 'Пользователь',
    columns: [
      columnHelper.accessor((row) => row.name, {
        id: 'name',
        header: 'Имя',
        cell: (info) => info.getValue(),
      }),
      columnHelper.accessor((row) => row.lastName, {
        id: 'lastName',
        header: 'Фамилия',
        cell: (info) => info.getValue(),
      }),
    ],
  }),
  columnHelper.group({
    header: 'Информация',
    columns: [
      columnHelper.accessor('age', {
        header: 'Возраст',
      }),
      columnHelper.accessor('visits', {
        header: 'Посещений',
      }),
      columnHelper.accessor('status', {
        header: 'Статус',
      }),
      columnHelper.accessor('progress', {
        header: 'Прогресс',
      }),
    ],
  }),
])
const rows = ref(
  Array.from({ length: 100 }).map((_, i) => ({
    id: i + 1,
    name: `Имя ${i + 1}`,
    lastName: `Фамилия ${i + 1}`,
    age: Math.floor(Math.random() * 100),
    visits: Math.floor(Math.random() * 100),
    status:
      Math.floor(Math.random() * 100) % 2 === 0 ? 'Успешно' : 'Есть вопросы',
    progress: Math.floor(Math.random() * 100),
  })),
)
const rowSelection = ref({})
const sorting = ref([])
const table = useVueTable({
  get columns() {
    return columns.value
  },
  get data() {
    return rows.value
  },
  state: {
    get rowSelection() {
      return rowSelection.value
    },
    get sorting() {
      return sorting.value
    },
  },

  enableRowSelection: true,
  onRowSelectionChange: (updateOrValue) => {
    rowSelection.value =
      typeof updateOrValue === 'function'
        ? updateOrValue(rowSelection.value)
        : updateOrValue
  },
  isMultiSortEvent: () => true,
  onSortingChange: (updaterOrValue) => {
    sorting.value =
      typeof updaterOrValue === 'function'
        ? updaterOrValue(sorting.value)
        : updaterOrValue
  },
  getSortedRowModel: getSortedRowModel(),
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
})
const pageSizes = [10, 20, 50]
const pageHandler = (data) => {
  const pageSize = data.rows
  const page = data.page
  table.setPageSize(pageSize)
  table.setPageIndex(page)
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
  <LayoutUiTemplate title="Tanstack table">
    <div class="content">
      <div class="table-tanstack">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr
                v-for="headerGroup in table.getHeaderGroups()"
                :key="headerGroup.id">
                <th
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  :colSpan="header.colSpan"
                  :style="
                    header.column.getCanSort() ? 'user-select: none;' : ''
                  "
                  @click="header.column.getToggleSortingHandler()?.($event)">
                  <template v-if="!header.isPlaceholder">
                    <div class="th-title">
                      <FlexRender
                        :render="header.column.columnDef.header"
                        :props="header.getContext()" />
                      <i-custom-triangle-down
                        v-show="
                          header.column.getCanSort() &&
                          header.column.getIsSorted() !== false
                        "
                        :style="
                          header.column.getIsSorted() === 'asc'
                            ? 'transform: rotate(180deg);'
                            : ''
                        " />
                    </div>
                  </template>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                :class="[
                  row.id == 2 ? 'row-disabled' : '',
                  row.getIsSelected() ? 'row-selected' : '',
                ]">
                <td v-for="cell in row.getVisibleCells()" :key="cell.id">
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()" />
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr
                v-for="footerGroup in table.getFooterGroups()"
                :key="footerGroup.id">
                <th
                  v-for="header in footerGroup.headers"
                  :key="header.id"
                  :colSpan="header.colSpan">
                  <FlexRender
                    v-if="!header.isPlaceholder"
                    :render="header.column.columnDef.footer"
                    :props="header.getContext()" />
                </th>
              </tr>
            </tfoot>
          </table>
        </div>
        <Paginator
          @page="pageHandler"
          :rows="table.getState().pagination.pageSize"
          :totalRecords="rows.length"
          :rowsPerPageOptions="pageSizes"
          :pt="paginatorDropdownStyle" />
      </div>
    </div>
  </LayoutUiTemplate>
</template>

<style lang="scss" scoped>
.table-wrapper {
  overflow-x: auto;
  overflow-y: hidden;
}
.table-tanstack {
  display: flex;
  flex-direction: column;
  gap: 0;
  table {
    width: 100%;
    padding-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    th,
    td {
      padding: 0.5rem 2rem 0.5rem 1rem;
    }
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
        @include active {
          background: var(--p-surface-100);
        }
        &:last-child {
          td {
            &:first-child {
              border-radius: 0 0 0 10px;
            }
            &:last-child {
              border-radius: 0 0 10px 0;
            }
          }
        }
      }
    }
  }
  :deep(.row-selected) {
    background: var(--p-surface-100);
  }
  :deep(.row-disabled) {
    opacity: 0.4;
    filter: grayscale(0.9);
    user-select: none;
    pointer-events: none;
  }
}
</style>
