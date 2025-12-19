<script setup>
import { ref, computed, watch } from 'vue'
import * as XLSX from 'xlsx'
import Swal from 'sweetalert2'
import { Utils } from '@/js/helpers/Utils.js'

const props = defineProps({
    items: { type: Array, default: () => [] },
    columns: { type: Array, required: true, },
    dayField: { type: String, default: 'fecha' }, // campo usado para agrupar por día
    showDayPagination: { type: Boolean, default: true },
    showExport: { type: Boolean, default: true },
    showSelection: { type: Boolean, default: true },
    isColActions:{ type: Boolean, default: false },
    selectableField: { type: String, default: 'id' },
    paginationMode: { type: String, default: 'day' }, // 'day' o 'items'
    itemsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100,250,500,1000,2500,5000,10000] },
    selected: {
        type: Array,
        default: () => []
    },
    validateSelection: {
        type: Function,
        default: null
    },
    clickableColumn: {
        type: String,
        default: null
    },
    footerSummary: {
        type: Boolean,
        default: false, // si true, muestra el pie de tabla
    },
    summaryFields: {
        type: Array,
        default: () => [], // campos a sumar (por ejemplo ['total'])
    },
    summaryColumn: {
        type: String,
        default: null // la columna donde aparecer el sumario
    },
    fixedColumns: {
        type: Number,
        default: 0 // número de columnas iniciales que no vienen en props.columns
    },
})

const emit = defineEmits(['selection-change','select-item', 'cell-click'])

const search = ref('')
const sortKey = ref('')
const sortOrder = ref('asc')
const selected = ref([])
const currentDayIndex = ref(0)
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalVisibleItems = computed(() => visibleItems.value.length)

const visibleTotals = computed(() => {
    const totals = {}
    props.summaryFields.forEach(field => {
        totals[field] = visibleItems.value.reduce((sum, item) => {
            const val = parseFloat(item[field]) || 0
            return sum + val
        }, 0)
    })
    return totals
})

function emitSelection(item, event) {
    if (props.validateSelection) {
        const allowed = props.validateSelection(item)
        if (!allowed) return
    }

    // 🔹 Manejamos la selección local
    const id = item[props.selectableField]
    const checked = event.target.checked

    if (checked && !selected.value.includes(id)) {
        selected.value.push(id)
    } else if (!checked) {
        selected.value = selected.value.filter(v => v !== id)
    }
    emit('selection-change', [...selected.value])
    emit('select-item', { item, checked: event.target.checked, event })
}

watch(selected, (val) => {
    emit('selection-change', val)
})

function isSelected(item) {
    return props.selected.includes(item.id)
}

const allFilteredSorted = computed(() => {
    let result = [...props.items]

    // 🔍 1) Búsqueda global en TODO el listado
    if (search.value.trim() !== '') {
        const query = search.value.toLowerCase()
        result = result.filter(item =>
            Object.values(item)
                .join(' ')
                .toLowerCase()
                .includes(query)
        )
    }

    // 🔁 2) Ordenamiento
    if (sortKey.value) {
        result = result.sort((a, b) => {
            let x = a[sortKey.value]
            let y = b[sortKey.value]

            if (typeof x === 'string') x = x.toLowerCase()
            if (typeof y === 'string') y = y.toLowerCase()

            if (x < y) return sortOrder.value === 'asc' ? -1 : 1
            if (x > y) return sortOrder.value === 'asc' ? 1 : -1
            return 0
        })
    }

    return result
})

const visibleItems = computed(() => {
    let result = allFilteredSorted.value

    if (props.paginationMode === 'day' && availableDays.value.length) {
        const day = availableDays.value[currentDayIndex.value]
        result = result.filter(item => Utils.getDatePart(item[props.dayField]) === day)
    }

    if (props.paginationMode === 'items') {
        const start = (currentPage.value - 1) * itemsPerPage.value
        const end = start + itemsPerPage.value
        result = result.slice(start, end)
    }

    return result
})

const totalPages = computed(() => {
    if (props.paginationMode !== 'items') return 1
    const total = allFilteredSorted.value.length
    return total === 0 ? 1 : Math.ceil(total / itemsPerPage.value)
})

const availableDays = computed(() => {
    if (props.paginationMode !== 'day') return []

    const days = allFilteredSorted.value
        .map(item => Utils.getDatePart(item[props.dayField]))
        .filter(Boolean)

    const unique = [...new Set(days)]
    return unique.sort((a, b) => new Date(b) - new Date(a)) // más recientes primero
})

const totalDays = computed(() => availableDays.value.length)

watch(availableDays, days => {
    if (!days.length) {
        currentDayIndex.value = 0
    } else if (currentDayIndex.value >= days.length) {
        currentDayIndex.value = 0
    }
})

watch(() => props.items, () => {
        if (!props.showDayPagination) return
        const groupedDays = Array.from(
            new Set(props.items.map(i => Utils.formatDate(i[props.dayField])))
        )
        availableDays.value = groupedDays.sort((a, b) => new Date(b) - new Date(a))
        totalDays.value = availableDays.value.length
    },
    { immediate: true }
)

function sortBy(field) {
    if (sortKey.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = field
        sortOrder.value = 'asc'
    }
}

async function exportToExcel() {
    const XLSX = await import('xlsx')

    // 🔹 Encabezados
    const headers = [props.columns.map(c => c.label)]

    // 🔹 Datos con detección automática
    const data = visibleItems.value.map(item =>
        props.columns.map(col => {
            const value = item[col.field]

            if (!value) return ''

            // 📅 Detección de fechas
            if (
                (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) ||
                value instanceof Date
            ) {
                return Utils.formatDateTime(value)
            }

            // 💰 Detección de números o totales
            if (
                typeof value === 'number' ||
                (typeof value === 'string' && value.match(/^\$?\d[\d,\.]*$/))
            ) {
                return parseFloat(value.toString().replace(/[^0-9.-]+/g, ''))
            }

            // Texto
            return value
        })
    )

    // 🔹 Buscar la columna "total"
    const totalIndex = props.columns.findIndex(c =>
        c.field.toLowerCase().includes('total')
    )

    // 🔹 Calcular total general
    let totalGeneral = 0
    if (totalIndex !== -1) {
        totalGeneral = visibleItems.value.reduce((sum, i) => {
            const val = parseFloat(
                (i[props.columns[totalIndex].field] || '').toString().replace(/[^0-9.-]+/g, '')
            )
            return sum + (isNaN(val) ? 0 : val)
        }, 0)
    }

    // 🔹 Combinar todo
    const worksheetData = [...headers, ...data]

    // 🔹 Agregar fila de total general
    if (totalIndex !== -1) {
        const totalRow = new Array(props.columns.length).fill('')
        totalRow[totalIndex - 1 >= 0 ? totalIndex - 1 : 0] = 'TOTAL GENERAL'
        totalRow[totalIndex] = totalGeneral
        worksheetData.push(totalRow)
    }

    // 🧮 Crear hoja y libro
    const ws = XLSX.utils.aoa_to_sheet(worksheetData)
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Datos')

    // 💅 Formato numérico para la columna Total
    if (totalIndex !== -1) {
        for (let R = 1; R < worksheetData.length; ++R) {
            const cell = XLSX.utils.encode_cell({ r: R, c: totalIndex })
            if (ws[cell]) {
                ws[cell].t = 'n'
                ws[cell].z = '$#,##0.00'
            }
        }
    }

    // 📏 Ajuste automático de ancho de columnas
    const colWidths = props.columns.map((col, colIndex) => {
        // Tomar el texto más largo entre encabezado, datos y total
        const maxLen = Math.max(
            col.label.length,
            ...worksheetData.map(row => (row[colIndex] ? row[colIndex].toString().length : 0))
        )
        // Un poco de margen adicional (factor ≈ 1.2)
        return { wch: Math.min(Math.max(maxLen * 1.2, 10), 50) }
    })
    ws['!cols'] = colWidths

    // 💾 Guardar archivo
    const filename = `${'reporte'}_${Utils.formatDate(new Date())}.xlsx`
    XLSX.writeFile(wb, filename)
}

function handleCellClick(item, field) {
    if (props.clickableColumn && field === props.clickableColumn) {
        emit('cell-click', item)
    }
}









</script>

<template>
    <div class="p-4 bg-slate-900/30 backdrop-blur-xl rounded-xl shadow-xl border border-white/10">

        <!-- 🔍 Buscador + contador -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4"
        >
            <!-- Search -->
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-lg
                           bg-slate-800 text-slate-200 placeholder-slate-400
                           border border-slate-700 focus:border-alumun-pino focus:ring-2 focus:ring-alumun-pino/50
                           transition-all"
                />
            </div>

            <p class="text-slate-300 text-sm">
                Mostrando <strong class="text-white">{{ visibleItems.length }}</strong> registros
            </p>
        </div>

        <!-- 🧾 Tabla -->
        <div class="overflow-auto rounded-xl border border-slate-700 shadow-inner max-h-[500px]">
            <table
                class="min-w-full text-sm bg-slate-900/40 backdrop-blur-xl"
            >
                <!-- Table Head -->
                <thead class="sticky top-0 z-10 bg-gradient-to-r from-alumun-guinda via-alumun-pino to-black text-white">
                <tr class="text-left text-xs font-bold uppercase tracking-wider">

                    <th
                        v-for="col in columns"
                        :key="col.field"
                        class="px-4 py-3 border-b border-white/20 select-none"
                        :class="[col.sortable ? 'cursor-pointer hover:bg-white/10 transition' : '',col.align || 'text-left']"
                        @click="col.sortable && sortBy(col.field)"
                    >
                        <div class="flex items-center gap-1">
                            {{ col.label }}

                            <span
                                v-if="col.sortable && sortKey === col.field"
                                class="text-[10px]"
                            >
                                    {{ sortOrder === 'asc' ? '▲' : '▼' }}
                                </span>
                        </div>
                    </th>
                    <th v-if="isColActions" class="px-4 py-3 text-center border-b border-white/20 select-none">
                        ⚙
                    </th>


                </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-slate-800/70">
                <tr
                    v-for="item in visibleItems"
                    :key="item.id"
                    class="transition-all hover:bg-slate-800/50 even:bg-slate-800/20"
                >
                    <!-- Checkbox -->
                    <td v-if="showSelection" class="px-4 py-3 text-center">
                        <input
                            type="checkbox"
                            class="w-4 h-4 rounded bg-slate-700 text-alumun-pino border-slate-600 focus:ring-alumun-pino"
                            :value="item.id"
                            :checked="isSelected(item)"
                            @change="emitSelection(item, $event)"
                        />
                    </td>

                    <!-- Data Cells -->
                    <td
                        v-for="col in props.columns"
                        :key="col.field"
                        :class="[
                                'px-4 py-3 text-slate-200',
                                col.align || 'text-left',
                                {
                                    'text-alumun-pino font-semibold cursor-pointer hover:underline':
                                    props.clickableColumn === col.field
                                }
                            ]"
                        @click.stop="handleCellClick(item, col.field)"
                    >
                        <slot :name="col.field" :item="item">
                            {{ item[col.field] }}
                        </slot>
                    </td>
                    <td v-if="isColActions" class="px-4 py-3 text-center w-20">
                        <slot name="actions" :item="item"></slot>
                    </td>
                </tr>

                <!-- No Items -->
                <tr v-if="!visibleItems.length">
                    <td
                        :colspan="columns.length + (showSelection ? 1 : 0)"
                        class="text-center text-slate-300 py-6 italic"
                    >
                        ⚠️ No hay registros.
                    </td>
                </tr>
                </tbody>

                <!-- Table Footer -->
<!--                <tfoot-->
<!--                    v-if="props.footerSummary"-->
<!--                    class="sticky bottom-0 bg-slate-900/70 backdrop-blur-md text-sm font-semibold border-t border-white/10"-->
<!--                >-->
<!--                <tr>-->
<!--                    <td-->
<!--                        v-for="n in props.fixedColumns"-->
<!--                        :key="'fixed-' + n"-->
<!--                        class="px-4 py-3"-->
<!--                    ></td>-->

<!--                    <td-->
<!--                        v-for="col in props.columns"-->
<!--                        :key="col.field"-->
<!--                        class="px-4 py-3"-->
<!--                        :class="[-->
<!--                                col.align || 'text-left',-->
<!--                                { 'text-green-400 font-bold text-right': col.field === props.summaryColumn }-->
<!--                            ]"-->
<!--                    >-->
<!--                        <template v-if="col.field === props.summaryColumn">-->
<!--                            💵 {{ Utils.formatCurrency(visibleTotals[props.summaryColumn] || 0) }}-->
<!--                        </template>-->

<!--                        <template v-else-if="col.field === props.columns[0].field">-->
<!--                            📊 {{ totalVisibleItems }} registros-->
<!--                        </template>-->
<!--                    </td>-->
<!--                </tr>-->
<!--                </tfoot>-->



                <tfoot
                    v-if="props.footerSummary"
                    class="sticky bottom-0 bg-slate-900/70 backdrop-blur-md text-sm font-semibold border-t border-white/10"
                >
                <tr>
                    <td
                        v-for="n in props.fixedColumns"
                        :key="'fixed-' + n"
                        class="px-4 py-3"
                    ></td>

                    <td
                        v-for="col in props.columns"
                        :key="col.field"
                        class="px-0 py-3"
                        :class="[
            col.align || 'text-left',
            {
                'text-green-400 font-bold text-right':
                    props.summaryFields.includes(col.field)
            }
        ]"
                    >
                        <!-- Si la columna está en summaryFields, mostramos el total de ese campo -->
                        <template v-if="props.summaryFields.includes(col.field)">
                            💵 {{ Utils.formatCurrency(visibleTotals[col.field] || 0) }}
                        </template>

                        <!-- En la primera columna mostramos número de registros -->
                        <template v-else-if="col.field === props.columns[0].field">
                            📊 {{ totalVisibleItems }} registros
                        </template>
                    </td>
                </tr>
                </tfoot>



            </table>
        </div>

        <!-- 🔽 Paginación + Export -->
        <div
            v-if="props.showDayPagination || props.showExport"
            class="flex flex-col md:flex-row justify-between items-center mt-4 gap-3 border-t pt-4"
        >
            <!-- 🔹 PAGINACIÓN POR DÍA -->
            <template v-if="props.paginationMode === 'day'">
                <div class="flex items-center gap-3">
                    <button
                        class="bg-gray-200 px-4 py-2 rounded-lg disabled:opacity-50"
                        :disabled="currentDayIndex === availableDays.length - 1"
                        @click="currentDayIndex++"
                    >
                        ← Día anterior
                    </button>

                    <button
                        class="bg-gray-200 px-4 py-2 rounded-lg disabled:opacity-50"
                        :disabled="currentDayIndex === 0"
                        @click="currentDayIndex--"
                    >
                        Día siguiente →
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label for="daySelect" class="text-sm text-gray-700 font-medium"
                    >Ver día:</label
                    >
                    <select
                        id="daySelect"
                        v-model="currentDayIndex"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option
                            v-for="(day, index) in availableDays"
                            :key="day"
                            :value="index"
                        >
                            {{ Utils.formatDate(day) }}
                        </option>
                    </select>
                </div>

                <p class="text-gray-600 text-sm">
                    Día {{ currentDayIndex + 1 }} de {{ totalDays }}
                    <span v-if="availableDays.length">
                        ({{ Utils.formatDate(availableDays[currentDayIndex]) }})
                      </span>
                </p>
            </template>

            <!-- 🔸 PAGINACIÓN POR NÚMERO DE ITEMS -->
            <template v-else>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-100">Items por página:</label>
                    <select
                        v-model="itemsPerPage"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option v-for="opt in props.itemsPerPageOptions" :key="opt" :value="opt">
                            {{ opt }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        class="bg-gray-300 text-gray-800 px-3 py-2 rounded-lg disabled:opacity-50"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                    >
                        ← Anterior
                    </button>
                    <p class="text-gray-100 text-sm">
                        Página {{ currentPage }} de {{ totalPages }}
                    </p>
                    <button
                        class="bg-gray-300 text-gray-800 px-3 py-2 rounded-lg disabled:opacity-50"
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                    >
                        Siguiente →
                    </button>
                </div>
            </template>

            <!-- Export -->
            <button
                v-if="props.showExport"
                @click="exportToExcel"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow-md transition"
            >
                <i class="fa-solid fa-file-excel"></i>
                <span class="text-sm font-medium">Exportar Excel</span>
            </button>
        </div>

    </div>
</template>

<style scoped>
tfoot {
    position: sticky;
    bottom: 0;
}
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
