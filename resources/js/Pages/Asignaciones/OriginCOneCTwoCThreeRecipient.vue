<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Swal from 'sweetalert2'
import { reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'

reloadRolesAndPermissions()

const page = usePage()

// Props (Inertia)
const combo1 = computed(() => page.props.combo1 ?? {})
const combo2 = computed(() => page.props.combo2 ?? {})
const combo3Prop = computed(() => page.props.combo3 ?? {})

const leftDisponibles = computed(() => page.props.leftDisponibles ?? [])
const rightAsignadosProp = computed(() => page.props.rightAsignados ?? [])

const titulo = computed(() => page.props.titulo ?? '')
const urlAdd = computed(() => page.props.urlAdd ?? '')
const urlDelete = computed(() => page.props.urlDelete ?? '')
const urlAsignados = computed(() => page.props.urlAsignados ?? '')

// Toast (SweetAlert2)
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
})

// — Estado local (para no mutar computed props) —
const combo3Local = ref({ ...(combo3Prop.value ?? {}) })
const rightAsignadosLocal = ref([...(rightAsignadosProp.value ?? [])])

// — Estados reactivos —
const selectedCombo1 = ref(0)
const selectedCombo2 = ref(0)
const selectedCombo3 = ref(0)

const leftSelected = ref([])
const rightSelected = ref([])

const fetching = ref(false)
const saving = ref(false)

const hasCombosBase = computed(() => !!selectedCombo1.value && !!selectedCombo2.value)
const hasCombosAll = computed(() => !!selectedCombo1.value && !!selectedCombo2.value && !!selectedCombo3.value)

// Snapshot por combinación (para contador de cambios)
const initialAssignedSnapshots = reactive({})
const currentKey = computed(() => {
    if (!hasCombosAll.value) return null
    return `${selectedCombo1.value}-${selectedCombo2.value}-${selectedCombo3.value}`
})

const diffChanges = computed(() => {
    const key = currentKey.value
    if (!key) return { added: 0, removed: 0, addedIds: [], removedIds: [] }

    const initialIds = initialAssignedSnapshots[key] ?? null
    if (!initialIds) return { added: 0, removed: 0, addedIds: [], removedIds: [] }

    const initial = new Set(initialIds)
    const current = new Set((rightAsignadosLocal.value ?? []).map(x => x.id))

    const addedIds = [...current].filter(id => !initial.has(id))
    const removedIds = [...initial].filter(id => !current.has(id))

    return {
        added: addedIds.length,
        removed: removedIds.length,
        addedIds,
        removedIds,
    }
})

const setSnapshotIfMissing = () => {
    const key = currentKey.value
    if (!key) return

    if (!initialAssignedSnapshots[key]) {
        initialAssignedSnapshots[key] = (rightAsignadosLocal.value ?? []).map(x => x.id)
    }
}

// — Fetch combos 3 (dependiente de combo1 y combo2) —
const fetchGrupos = async () => {
    if (!hasCombosBase.value) return

    fetching.value = true

    // reset UI
    combo3Local.value = {}
    selectedCombo3.value = 0
    rightAsignadosLocal.value = []
    rightSelected.value = []

    try {
        await router.get(
            route(urlAsignados.value, {
                combo1_id: selectedCombo1.value,
                combo2_id: selectedCombo2.value,
                combo3_id: selectedCombo3.value,
            }),
            {},
            {
                preserveState: true,
                preserveScroll: false,
                only: ['combo3'],
            }
        )
    } finally {
        fetching.value = false
    }
}

// — Fetch asignados + disponibles (dependiente de combo1/2/3) —
const fetchAsignaciones = async () => {
    if (!hasCombosAll.value) return

    fetching.value = true
    rightAsignadosLocal.value = []
    rightSelected.value = []
    leftSelected.value = []

    try {
        await router.get(
            route(urlAsignados.value, {
                combo1_id: selectedCombo1.value,
                combo2_id: selectedCombo2.value,
                combo3_id: selectedCombo3.value,
            }),
            {},
            {
                preserveState: true,
                preserveScroll: false,
                only: ['rightAsignados', 'leftDisponibles'],
            }
        )

        // snapshot al primer load de esta combinación
        setSnapshotIfMissing()
    } finally {
        fetching.value = false
    }
}

// — Asignar/grabar cambios —
const agregarItems = () => {
    const ids = leftSelected.value

    if (!hasCombosAll.value || !ids.length || saving.value) return

    saving.value = true

    router.post(
        route(urlAdd.value),
        {
            ids,
            combo1_id: selectedCombo1.value,
            combo2_id: selectedCombo2.value,
            combo3_id: selectedCombo3.value,
        },
        {
            preserveScroll: true,
            onSuccess: async () => {
                toast.fire({ icon: 'success', title: 'Asignados correctamente' })
                leftSelected.value = []
                await fetchAsignaciones()
            },
            onError: (errors) => {
                const msg = errors?.message || 'Error asignando elementos'
                toast.fire({ icon: 'error', title: msg })
            },
            onFinish: () => {
                saving.value = false
            },
        }
    )
}

const quitarItems = () => {
    const ids = rightSelected.value

    if (!hasCombosAll.value || !ids.length || saving.value) return

    saving.value = true

    router.post(
        route(urlDelete.value),
        {
            ids,
            combo1_id: selectedCombo1.value,
            combo2_id: selectedCombo2.value,
            combo3_id: selectedCombo3.value,
        },
        {
            preserveScroll: true,
            onSuccess: async () => {
                toast.fire({ icon: 'success', title: 'Removidos correctamente' })
                rightSelected.value = []
                await fetchAsignaciones()
            },
            onError: (errors) => {
                const msg = errors?.message || 'Error quitando elementos'
                toast.fire({ icon: 'error', title: msg })
            },
            onFinish: () => {
                saving.value = false
            },
        }
    )
}

// Watchers: sincronizar props -> local
watch(combo3Prop, (newVal) => {
    combo3Local.value = newVal ?? {}

    const keys = Object.keys(combo3Local.value)
    if (keys.length) {
        const current = String(selectedCombo3.value || '')
        if (!current || !keys.includes(current)) {
            selectedCombo3.value = keys[0]
        }
    }
})

watch(rightAsignadosProp, (newVal) => {
    rightAsignadosLocal.value = Array.isArray(newVal) ? newVal : []
    setSnapshotIfMissing()
})

watch(selectedCombo2, () => {
    // cuando cambia combo2, reinicia el panel derecho
    rightAsignadosLocal.value = []
    rightSelected.value = []
    leftSelected.value = []
    selectedCombo3.value = 0
})

watch(selectedCombo3, async (val) => {
    if (!val) return
    // al seleccionar combo3, carga asignaciones
    await fetchAsignaciones()
})

onMounted(async () => {
    const combo1Keys = Object.keys(combo1.value)
    const combo2Keys = Object.keys(combo2.value)

    if (combo1Keys.length && (selectedCombo1.value === 0 || !combo1Keys.includes(String(selectedCombo1.value)))) {
        selectedCombo1.value = combo1Keys[0]
    }

    if (combo2Keys.length && (selectedCombo2.value === 0 || !combo2Keys.includes(String(selectedCombo2.value)))) {
        selectedCombo2.value = combo2Keys[0]
    }

    // Cargar combo3 con la selección inicial
    await fetchGrupos()

    // Si combo3 llegó por props en el primer render
    const combo3Keys = Object.keys(combo3Local.value)
    if (combo3Keys.length && (selectedCombo3.value === 0 || !combo3Keys.includes(String(selectedCombo3.value)))) {
        selectedCombo3.value = combo3Keys[0]
    }

    // Cargar asignaciones iniciales si ya hay combo3
    if (selectedCombo3.value) {
        await fetchAsignaciones()
    }
})
</script>

<template>
    <Head :title="titulo" />

    <AuthenticatedLayout>
        <template #title>
            {{ titulo }}
        </template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">{{ titulo }}</h1>
                    <p class="text-sm text-slate-300">
                        Selecciona las opciones y asigna / quita elementos.
                        <span v-if="fetching" class="text-slate-400">(Cargando...)</span>
                        <span v-if="saving" class="text-slate-400">(Guardando...)</span>
                    </p>
                </div>

                <div v-if="hasCombosAll" class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-white/5 border border-white/10 text-slate-300">
                        Cambios:
                        <span class="text-emerald-300">+{{ diffChanges.added }}</span>
                        <span class="text-rose-300">-{{ diffChanges.removed }}</span>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-[70vh]">
                <!-- Panel Disponibles -->
                <section class="lg:col-span-5 flex flex-col rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <header class="p-4 border-b border-white/10 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Disponibles</h3>
                            <p class="text-xs text-slate-400">
                                {{ leftDisponibles.length }} disponibles · {{ leftSelected.length }} seleccionados
                            </p>
                        </div>
                    </header>

                    <div class="flex-1 overflow-auto p-2">
                        <template v-if="leftDisponibles.length">
                            <label
                                v-for="item in leftDisponibles"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="leftSelected"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40"
                                />
                                <span class="text-sm text-slate-200">{{ item.descripcion }}</span>
                            </label>
                        </template>

                        <div v-else class="py-10 text-center text-sm text-slate-400">
                            ✨ No hay elementos disponibles
                        </div>
                    </div>

                    <footer class="p-3 border-t border-white/10 bg-black/10">
                        <span class="text-xs text-slate-400">{{ leftDisponibles.length }} elementos listados</span>
                    </footer>
                </section>

                <!-- Panel de Controles -->
                <div class="lg:col-span-2 flex flex-col items-stretch justify-center gap-3">
                    <button
                        type="button"
                        @click="agregarItems"
                        :disabled="saving || fetching || !leftSelected.length || !hasCombosAll"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && !fetching && leftSelected.length && hasCombosAll)
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                            : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    >
                        <svg v-if="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <span>{{ saving ? 'Asignando...' : 'Asignar →' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="quitarItems"
                        :disabled="saving || fetching || !rightSelected.length || !hasCombosAll"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && !fetching && rightSelected.length && hasCombosAll)
                            ? 'bg-rose-600 hover:bg-rose-700 text-white'
                            : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    >
                        <svg v-if="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <span>{{ saving ? 'Removiendo...' : '← Quitar' }}</span>
                    </button>
                </div>

                <!-- Panel Asignados -->
                <section class="lg:col-span-5 flex flex-col rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <header class="p-4 border-b border-white/10 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <select
                                v-model="selectedCombo1"
                                @change="fetchGrupos"
                                class="w-full rounded-lg bg-black/20 border border-white/10 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                            >
                                <option v-for="(descripcion, id) in combo1" :key="id" :value="id">
                                    {{ descripcion }}
                                </option>
                            </select>

                            <select
                                v-model="selectedCombo2"
                                @change="fetchGrupos"
                                class="w-full rounded-lg bg-black/20 border border-white/10 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                            >
                                <option v-for="(descripcion, id) in combo2" :key="id" :value="id">
                                    {{ descripcion }}
                                </option>
                            </select>

                            <select
                                v-model="selectedCombo3"
                                :disabled="!Object.keys(combo3Local).length"
                                class="w-full rounded-lg bg-black/20 border border-white/10 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:opacity-50"
                            >
                                <option :value="0" disabled>-- Selecciona --</option>
                                <option v-for="(descripcion, id) in combo3Local" :key="id" :value="id">
                                    {{ descripcion }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-white">Asignados</h3>
                                <p class="text-xs text-slate-400">
                                    {{ rightAsignadosLocal.length }} asignados · {{ rightSelected.length }} seleccionados
                                </p>
                            </div>

                            <div v-if="hasCombosAll" class="text-xs text-slate-400">
                                <span class="px-2 py-1 rounded bg-white/5 border border-white/10">
                                    +<span class="text-emerald-300">{{ diffChanges.added }}</span>
                                    -<span class="text-rose-300">{{ diffChanges.removed }}</span>
                                </span>
                            </div>
                        </div>
                    </header>

                    <div class="flex-1 overflow-auto p-2">
                        <template v-if="rightAsignadosLocal.length">
                            <label
                                v-for="item in rightAsignadosLocal"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="rightSelected"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-rose-500 focus:ring-rose-500/40"
                                />
                                <span class="text-sm text-slate-200">{{ item.descripcion }}</span>
                            </label>
                        </template>

                        <div v-else class="py-10 text-center text-sm text-slate-400">
                            🎯 No hay elementos asignados
                        </div>
                    </div>

                    <footer class="p-3 border-t border-white/10 bg-black/10">
                        <span class="text-xs text-slate-400">{{ rightAsignadosLocal.length }} elementos asignados</span>
                    </footer>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
