<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { usePeriodoVigente } from "@/Composables/usePeriodoVigente.js"

const props = defineProps({
    periodo: Object,
    folder: String,
    files: Array,
    stats: Object,
    runUrl: String,

    // ✅ nuevo
    itemsUrl: String,
})

const { labelPeriodoVigente } = usePeriodoVigente()

const running = ref(false)
const result = ref(null)

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
})

const hasFiles = computed(() => (props.files?.length ?? 0) > 0)

const run = async () => {
    if (!hasFiles.value) {
        return toast.fire({ icon: 'warning', title: 'No hay archivos XLSX en la carpeta del periodo vigente.' })
    }

    const confirm = await Swal.fire({
        title: '¿Ejecutar conciliación?',
        html: `
      <div style="text-align:left">
        <p>Periodo vigente: <b>${labelPeriodoVigente.value ?? ''}</b></p>
        <p>Carpeta: <code>${props.folder}</code></p>
        <p>Archivos: <b>${props.files.length}</b></p>
      </div>
    `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, conciliar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    })

    if (!confirm.isConfirmed) return

    running.value = true
    result.value = null

    try {
        const { data } = await axios.post(props.runUrl, {})
        if (!data?.ok) throw new Error(data?.message || 'Error desconocido')

        result.value = data
        toast.fire({ icon: 'success', title: 'Conciliación terminada' })

        // ✅ refrescar listado
        await fetchItems({ page: 1 })
    } catch (e) {
        toast.fire({ icon: 'error', title: e?.message || 'Error al conciliar' })
    } finally {
        running.value = false
    }
}

const badge = (ok) => ok
    ? 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30'
    : 'bg-rose-500/15 text-rose-200 border-rose-400/30'

const statusPill = (st) => {
    if (st === 'validated') return 'bg-emerald-600 text-white'
    if (st === 'mismatch') return 'bg-rose-600 text-white'
    if (st === 'not_found') return 'bg-amber-500 text-black'
    if (st === 'duplicate_row') return 'bg-amber-400 text-black'
    return 'bg-white/10 text-white'
}

/** =========================
 *  ✅ LISTADO DE RECIBOS
 * ========================= */
const itemsLoading = ref(false)
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null })

const itemsQuery = ref('')
const validatedFilter = ref('all') // all | 1 | 0
const perPage = ref(25)

let _t = null
const debounce = (fn, ms = 300) => {
    clearTimeout(_t)
    _t = setTimeout(fn, ms)
}

const fetchItems = async ({ page } = {}) => {
    if (!props.itemsUrl) return
    itemsLoading.value = true

    try {
        const params = {
            page: page ?? meta.value.current_page ?? 1,
            q: itemsQuery.value || '',
            per_page: perPage.value,
        }

        if (validatedFilter.value === '1') params.validated = '1'
        else if (validatedFilter.value === '0') params.validated = '0'

        const { data } = await axios.get(props.itemsUrl, { params })
        if (!data?.ok) throw new Error(data?.message || 'No se pudo cargar el listado')

        items.value = data.data ?? []
        meta.value = data.meta ?? meta.value
    } catch (e) {
        toast.fire({ icon: 'error', title: e?.message || 'Error al cargar recibos' })
    } finally {
        itemsLoading.value = false
    }
}

const goPage = (p) => {
    const next = Math.max(1, Math.min(p, meta.value.last_page || 1))
    fetchItems({ page: next })
}

watch([itemsQuery, validatedFilter, perPage], () => {
    debounce(() => fetchItems({ page: 1 }), 350)
})

onMounted(() => {
    fetchItems({ page: 1 })
})

const itemRowClass = (it) => it.validado
    ? 'bg-emerald-500/5'
    : 'bg-rose-500/5'

const okText = (v) => (v ? 'OK' : 'NO')

const validatedPill = (v) =>
    v
        ? 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30'
        : 'bg-rose-500/15 text-rose-200 border-rose-400/30'
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Conciliaciones" />
        <template #title>Conciliaciones</template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">Conciliaciones</h1>
                    <p class="text-sm text-slate-300 mt-1">
                        Todo movimiento se realiza en el <span class="text-emerald-200 font-semibold">Periodo Vigente</span>.
                    </p>
                    <div class="mt-2 text-xs text-white/70">
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-white/5 border border-white/10">
              📅 <span class="font-semibold text-white">{{ labelPeriodoVigente }}</span>
            </span>
                        <span class="ml-2 inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-white/5 border border-white/10">
              📁 <code class="text-white/80">{{ folder }}</code>
            </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                        <div class="text-[10px] text-white/60">Recibos del periodo</div>
                        <div class="text-sm font-semibold text-white">{{ stats?.recibos ?? 0 }}</div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                        <div class="text-[10px] text-white/60">Validados</div>
                        <div class="text-sm font-semibold text-emerald-200">{{ stats?.validados ?? 0 }}</div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                        <div class="text-[10px] text-white/60">No validados</div>
                        <div class="text-sm font-semibold text-rose-200">{{ stats?.no_validados ?? 0 }}</div>
                    </div>

                    <button
                        class="px-4 py-2 rounded-xl font-semibold border border-white/10 transition flex items-center gap-2"
                        :class="(!running && hasFiles) ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                        :disabled="running || !hasFiles"
                        @click="run"
                    >
                        <svg v-if="running" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <span>{{ running ? 'Conciliando...' : 'Ejecutar conciliación' }}</span>
                    </button>
                </div>
            </div>

            <!-- Archivos detectados -->
            <div class="rounded-xl border border-white/10 bg-black/20 p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Archivos planos detectados</h3>
                        <p class="text-xs text-white/60">Se recorren todos, uno por uno.</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full border bg-white/5 border-white/10 text-white/80">
            {{ files?.length ?? 0 }} XLSX
          </span>
                </div>

                <div v-if="!hasFiles" class="text-sm text-amber-200 mt-3">
                    No hay archivos *.xlsx en la carpeta del periodo vigente.
                </div>

                <div v-else class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div
                        v-for="f in files"
                        :key="f.path"
                        class="px-3 py-2 rounded-lg bg-black/20 border border-white/10 text-sm text-white/80"
                    >
                        📄 {{ f.name }}
                    </div>
                </div>
            </div>

            <!-- ✅ LISTADO DE RECIBOS (periodo vigente) -->
            <div class="rounded-xl border border-white/10 bg-black/20 overflow-hidden mb-6">
                <div class="p-4 border-b border-white/10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Recibos del periodo vigente</h3>
                        <p class="text-xs text-white/60">Validados / No validados (sin incluir filas fuera de periodo).</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input
                            v-model="itemsQuery"
                            type="text"
                            placeholder="Buscar por RPU o periodo..."
                            class="w-full sm:w-64 rounded-xl bg-black/20 border border-white/10 text-white placeholder:text-white/40 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                        />

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="px-3 py-2 rounded-xl border text-xs font-semibold transition"
                                :class="validatedFilter==='all' ? 'bg-white/10 border-white/20 text-white' : 'bg-black/10 border-white/10 text-white/70 hover:bg-white/5'"
                                @click="validatedFilter='all'"
                            >
                                Todos
                            </button>
                            <button
                                type="button"
                                class="px-3 py-2 rounded-xl border text-xs font-semibold transition"
                                :class="validatedFilter==='1' ? 'bg-emerald-500/15 border-emerald-400/30 text-emerald-200' : 'bg-black/10 border-white/10 text-white/70 hover:bg-white/5'"
                                @click="validatedFilter='1'"
                            >
                                Validados
                            </button>
                            <button
                                type="button"
                                class="px-3 py-2 rounded-xl border text-xs font-semibold transition"
                                :class="validatedFilter==='0' ? 'bg-rose-500/15 border-rose-400/30 text-rose-200' : 'bg-black/10 border-white/10 text-white/70 hover:bg-white/5'"
                                @click="validatedFilter='0'"
                            >
                                No validados
                            </button>
                        </div>

                        <select
                            v-model.number="perPage"
                            class="rounded-xl bg-black/20 border border-white/10 text-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="1000">1000</option>
                            <option :value="2000">2000</option>
                        </select>
                    </div>
                </div>

                <div class="p-4">
                    <div class="flex items-center justify-between text-xs text-white/60 mb-3">
                        <div>
                            <span v-if="meta.total">Mostrando {{ meta.from }}–{{ meta.to }} de {{ meta.total }}</span>
                            <span v-else>Sin registros</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10 text-xs disabled:opacity-40"
                                :disabled="itemsLoading || meta.current_page <= 1"
                                @click="goPage(meta.current_page - 1)"
                            >
                                ← Anterior
                            </button>
                            <button
                                class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10 text-xs disabled:opacity-40"
                                :disabled="itemsLoading || meta.current_page >= meta.last_page"
                                @click="goPage(meta.current_page + 1)"
                            >
                                Siguiente →
                            </button>
                        </div>
                    </div>

                    <div v-if="itemsLoading" class="text-sm text-white/60 py-6 text-center">
                        Cargando recibos...
                    </div>

                    <div v-else-if="!items.length" class="text-sm text-white/60 py-6 text-center">
                        No hay recibos para mostrar con este filtro.
                    </div>

                    <div v-else class="overflow-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                            <tr class="text-white/70 border-b border-white/10">
                                <th class="py-2 pr-3">ID</th>
                                <th class="py-2 pr-3">RPU</th>
                                <th class="py-2 pr-3">Periodo</th>
                                <th class="py-2 pr-3">Total</th>
                                <th class="py-2 pr-3">Consumo</th>
                                <th class="py-2 pr-3">Desde</th>
                                <th class="py-2 pr-3">Hasta</th>
                                <th class="py-2 pr-3">Validado</th>
                                <th class="py-2 pr-3">Checks</th>
                                <th class="py-2 pr-3">Conciliado</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr
                                v-for="it in items"
                                :key="it.id"
                                class="border-b border-white/5 text-white/80"
                                :class="itemRowClass(it)"
                            >
                                <td class="py-2 pr-3">{{ it.id }}</td>
                                <td class="py-2 pr-3 font-semibold">{{ it.rpu }}</td>
                                <td class="py-2 pr-3">{{ it.periodo }}</td>
                                <td class="py-2 pr-3">{{ it.total }}</td>
                                <td class="py-2 pr-3">{{ it.consumo }}</td>
                                <td class="py-2 pr-3">{{ it.desde }}</td>
                                <td class="py-2 pr-3">{{ it.hasta }}</td>

                                <td class="py-2 pr-3">
                    <span class="px-2 py-1 rounded-full border text-[10px]" :class="validatedPill(it.validado)">
                      {{ it.validado ? 'VALIDADO' : 'NO' }}
                    </span>
                                </td>

                                <td class="py-2 pr-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.rpu_ok)">RPU {{ okText(it.rpu_ok) }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.periodo_ok)">Per {{ okText(it.periodo_ok) }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.total_ok)">Tot {{ okText(it.total_ok) }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.consumo_ok)">Con {{ okText(it.consumo_ok) }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.desde_ok)">Des {{ okText(it.desde_ok) }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(it.hasta_ok)">Has {{ okText(it.hasta_ok) }}</span>
                                    </div>
                                </td>

                                <td class="py-2 pr-3 text-white/70">
                                    {{ it.conciliado_at ?? '—' }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-white/60">
                        <div>Página {{ meta.current_page }} de {{ meta.last_page }}</div>
                        <button
                            class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-white/80 hover:bg-white/10 text-xs"
                            :disabled="itemsLoading"
                            @click="fetchItems({ page: meta.current_page })"
                        >
                            Refrescar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resultados (cuando corres conciliación) -->
            <div v-if="result" class="space-y-4">
                <div class="rounded-xl border border-white/10 bg-black/20 p-4">
                    <h3 class="text-sm font-semibold text-white">Resumen global</h3>
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-6 gap-3 text-xs">
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">Filas leídas</div>
                            <div class="text-white font-semibold text-sm">{{ result.global.rows_read }}</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">Matched</div>
                            <div class="text-white font-semibold text-sm">{{ result.global.rows_matched }}</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">No encontrados</div>
                            <div class="text-amber-200 font-semibold text-sm">{{ result.global.rows_not_found }}</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">Validados</div>
                            <div class="text-emerald-200 font-semibold text-sm">{{ result.global.rows_validated }}</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">Mismatch</div>
                            <div class="text-rose-200 font-semibold text-sm">{{ result.global.rows_mismatch }}</div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                            <div class="text-white/60">Recibos DB</div>
                            <div class="text-white font-semibold text-sm">{{ result.global.db_items }}</div>
                        </div>
                    </div>
                </div>

                <div v-for="file in result.files" :key="file.path" class="rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <div class="p-4 flex items-start justify-between gap-3 border-b border-white/10">
                        <div>
                            <div class="text-sm font-semibold text-white">📄 {{ file.file }}</div>
                            <div class="text-xs text-white/60 mt-1">
                                Filas: {{ file.rows_read }} · Matched: {{ file.matched }} ·
                                <span class="text-emerald-200">Validados: {{ file.validated }}</span> ·
                                <span class="text-rose-200">Mismatch: {{ file.mismatch }}</span> ·
                                <span class="text-amber-200">No encontrados: {{ file.not_found }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-1 rounded-full border bg-white/5 border-white/10 text-white/80">
              {{ file.details?.length ?? 0 }} incidencias
            </span>
                    </div>

                    <div v-if="(file.details?.length ?? 0) === 0" class="p-4 text-sm text-emerald-200">
                        ✅ Sin incidencias.
                    </div>

                    <div v-else class="p-4 overflow-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                            <tr class="text-white/70 border-b border-white/10">
                                <th class="py-2 pr-2">Fila</th>
                                <th class="py-2 pr-2">Estado</th>
                                <th class="py-2 pr-2">RPU</th>
                                <th class="py-2 pr-2">Periodo XLSX</th>
                                <th class="py-2 pr-2">Total XLSX</th>
                                <th class="py-2 pr-2">Consumo XLSX</th>
                                <th class="py-2 pr-2">Desde</th>
                                <th class="py-2 pr-2">Hasta</th>
                                <th class="py-2 pr-2">Checks</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="d in file.details"
                                :key="d.row + '-' + d.status"
                                class="border-b border-white/5 text-white/80"
                            >
                                <td class="py-2 pr-2">{{ d.row }}</td>
                                <td class="py-2 pr-2">
                    <span class="px-2 py-1 rounded text-[10px]" :class="statusPill(d.status)">
                      {{ d.status }}
                    </span>
                                </td>
                                <td class="py-2 pr-2 font-semibold">{{ d.rpu }}</td>
                                <td class="py-2 pr-2">{{ d.periodo_xlsx ?? d.xlsx?.periodo }}</td>
                                <td class="py-2 pr-2">{{ d.total_xlsx ?? d.xlsx?.total }}</td>
                                <td class="py-2 pr-2">{{ d.consumo_xlsx ?? d.xlsx?.consumo }}</td>
                                <td class="py-2 pr-2">{{ d.desde_xlsx ?? d.xlsx?.desde }}</td>
                                <td class="py-2 pr-2">{{ d.hasta_xlsx ?? d.xlsx?.hasta }}</td>
                                <td class="py-2 pr-2">
                                    <div v-if="d.checks" class="flex flex-wrap gap-1">
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.rpu_ok)">RPU</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.periodo_ok)">Periodo</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.total_ok)">Total</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.consumo_ok)">Consumo</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.desde_ok)">Desde</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.hasta_ok)">Hasta</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[10px]" :class="badge(d.checks.validado)">VALIDADO</span>
                                    </div>
                                    <div v-else class="text-white/60">{{ d.msg }}</div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
