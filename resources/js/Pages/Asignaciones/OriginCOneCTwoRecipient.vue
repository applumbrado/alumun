<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Swal from 'sweetalert2'
import { is, can, reloadRolesAndPermissions } from 'laravel-permission-to-vuejs'

reloadRolesAndPermissions()

const page = usePage()

// Props desde Inertia
const titulo = computed(() => page.props.titulo ?? 'Asignación')
const combo1Options = computed(() => page.props.combo1 ?? {})
const combo2Options = computed(() => page.props.combo2 ?? {})
const leftDisponibles = computed(() => page.props.leftDisponibles ?? [])
const rightAsignados = computed(() => page.props.rightAsignados ?? {})

const urlAdd = computed(() => page.props.urlAdd)
const urlDelete = computed(() => page.props.urlDelete)
const urlAsignados = computed(() => page.props.urlAsignados)

// Estado UI
const selectedCombo1 = ref(0)
const selectedCombo2 = ref(0)
const leftSelected = ref([])
const rightSelected = ref([])

const fetching = ref(false)
const saving = ref(false)

// Búsquedas
const searchAvailable = ref('')
const searchAssigned = ref('')

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
})

const normalize = (s = '') => {
    return String(s)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
}

const comboKey = computed(() => `${selectedCombo1.value}-${selectedCombo2.value}`)

// Normalizamos a arrays para UI
const disponiblesArray = computed(() => {
    const arr = Array.isArray(leftDisponibles.value) ? leftDisponibles.value : []
    return arr.map((it) => ({
        ...it,
        id: String(it.id),
        label: it.grupo ?? it.nombre ?? it.name ?? '',
    }))
})

const asignadosArray = computed(() => {
    const obj = rightAsignados.value ?? {}
    return Object.entries(obj).map(([id, nombre]) => ({
        id: String(id),
        label: String(nombre ?? ''),
    }))
})

const filteredDisponibles = computed(() => {
    const q = normalize(searchAvailable.value)
    if (!q) return disponiblesArray.value

    return disponiblesArray.value.filter((it) => {
        return normalize(it.label).includes(q) || String(it.id).includes(q)
    })
})

const filteredAsignados = computed(() => {
    const q = normalize(searchAssigned.value)
    if (!q) return asignadosArray.value

    return asignadosArray.value.filter((it) => {
        return normalize(it.label).includes(q) || String(it.id).includes(q)
    })
})

const selectAllAvailable = () => {
    leftSelected.value = filteredDisponibles.value.map((x) => x.id)
}
const clearAvailable = () => {
    leftSelected.value = []
}
const selectAllAssigned = () => {
    rightSelected.value = filteredAsignados.value.map((x) => x.id)
}
const clearAssigned = () => {
    rightSelected.value = []
}

// Snapshot inicial por combinación (para contador de cambios)
const initialAssignedByKey = ref({})

watch(
    [comboKey, rightAsignados],
    () => {
        const key = comboKey.value
        if (!key || key === '0-0') return

        if (!(key in initialAssignedByKey.value)) {
            initialAssignedByKey.value[key] = Object.keys(rightAsignados.value ?? {}).map(String)
        }
    },
    { immediate: true }
)

const diffChanges = computed(() => {
    const key = comboKey.value
    if (!key || key === '0-0') return { added: 0, removed: 0 }

    const initial = new Set((initialAssignedByKey.value[key] ?? []).map(String))
    const current = new Set(asignadosArray.value.map((x) => String(x.id)))

    const added = [...current].filter((id) => !initial.has(id)).length
    const removed = [...initial].filter((id) => !current.has(id)).length

    return { added, removed }
})

const fetchGrupos = async () => {
    if (!selectedCombo1.value || !selectedCombo2.value) return

    fetching.value = true

    await new Promise((resolve) => {
        router.get(
            route(urlAsignados.value, {
                combo1_id: selectedCombo1.value,
                combo2_id: selectedCombo2.value,
            }),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                only: ['rightAsignados'],
                onFinish: () => {
                    fetching.value = false
                    resolve(true)
                },
            }
        )
    })
}

const agregarItems = () => {
    const ids = leftSelected.value
    const combo1 = selectedCombo1.value
    const combo2 = selectedCombo2.value

    if (!ids.length || !combo1 || !combo2 || saving.value || fetching.value) return

    saving.value = true

    router.post(
        route(urlAdd.value),
        { ids, combo1_id: combo1, combo2_id: combo2 },
        {
            preserveScroll: true,
            onSuccess: async () => {
                toast.fire({ icon: 'success', title: 'Asignados correctamente' })
                leftSelected.value = []
                await fetchGrupos()
            },
            onError: (errors) => {
                const msg = errors?.message || 'Error al asignar'
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
    const combo1 = selectedCombo1.value
    const combo2 = selectedCombo2.value

    if (!ids.length || !combo1 || !combo2 || saving.value || fetching.value) return

    saving.value = true

    router.post(
        route(urlDelete.value),
        { ids, combo1_id: combo1, combo2_id: combo2 },
        {
            preserveScroll: true,
            onSuccess: async () => {
                toast.fire({ icon: 'success', title: 'Removidos correctamente' })
                rightSelected.value = []
                await fetchGrupos()
            },
            onError: (errors) => {
                const msg = errors?.message || 'Error al quitar'
                toast.fire({ icon: 'error', title: msg })
            },
            onFinish: () => {
                saving.value = false
            },
        }
    )
}

const initCombosIfNeeded = () => {
    const keys1 = Object.keys(combo1Options.value ?? {})
    const keys2 = Object.keys(combo2Options.value ?? {})

    if (keys1.length && (!selectedCombo1.value || selectedCombo1.value === 0)) {
        selectedCombo1.value = keys1[0]
    }
    if (keys2.length && (!selectedCombo2.value || selectedCombo2.value === 0)) {
        selectedCombo2.value = keys2[0]
    }
}

onMounted(async () => {
    initCombosIfNeeded()
    await fetchGrupos()
})

watch([selectedCombo1, selectedCombo2], async () => {
    leftSelected.value = []
    rightSelected.value = []
    searchAvailable.value = ''
    searchAssigned.value = ''

    await fetchGrupos()
})
</script>

<template>
    <Head :title="titulo" />

    <AuthenticatedLayout>
        <template #title>
            {{ titulo }}
        </template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <div class="flex flex-col gap-1 mb-6">
                <h1 class="text-xl font-bold text-white">{{ titulo }}</h1>
                <p class="text-sm text-slate-300">
                    Selecciona tus opciones y mueve elementos entre disponibles y asignados.
                </p>
            </div>

            <!-- Selectores + cambios -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-slate-200 mb-2">Opción 1</label>
                    <select
                        v-model="selectedCombo1"
                        class="w-full rounded-lg bg-black/20 border border-white/10 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                    >
                        <option v-for="(label, id) in combo1Options" :key="id" :value="id">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-slate-200 mb-2">Opción 2</label>
                    <select
                        v-model="selectedCombo2"
                        class="w-full rounded-lg bg-black/20 border border-white/10 text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                    >
                        <option v-for="(label, id) in combo2Options" :key="id" :value="id">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <div class="w-full flex items-center justify-between gap-2">
                        <span class="px-3 py-2 rounded-lg border border-white/10 bg-white/5 text-xs text-slate-200">
                            Cambios:
                            <span class="text-emerald-300">+{{ diffChanges.added }}</span>
                            <span class="text-rose-300">-{{ diffChanges.removed }}</span>
                        </span>

                        <span v-if="fetching" class="text-xs text-slate-400">Cargando...</span>
                        <span v-else-if="saving" class="text-xs text-slate-400">Guardando...</span>
                    </div>
                </div>
            </div>

            <!-- Paneles -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                <!-- Disponibles -->
                <div class="lg:col-span-5 rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <div class="p-4 border-b border-white/10 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Disponibles</h3>
                            <p class="text-xs text-slate-400">
                                {{ disponiblesArray.length }} disponibles · {{ leftSelected.length }} seleccionados
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAllAvailable"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Todo
                            </button>
                            <button
                                type="button"
                                @click="clearAvailable"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        <input
                            v-model="searchAvailable"
                            type="text"
                            placeholder="Buscar..."
                            class="w-full mb-3 rounded-lg bg-black/20 border border-white/10 text-white placeholder:text-slate-500 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                        />

                        <div class="max-h-[420px] overflow-auto pr-1 space-y-1">
                            <label
                                v-for="item in filteredDisponibles"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="leftSelected"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40"
                                />
                                <span class="text-sm text-slate-200">
                                    {{ item.label }}
                                    <span class="text-xs text-slate-500">(#{{ item.id }})</span>
                                </span>
                            </label>

                            <div v-if="!filteredDisponibles.length" class="text-sm text-slate-400 py-6 text-center">
                                No hay elementos disponibles.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controles -->
                <div class="lg:col-span-2 flex flex-col items-stretch justify-center gap-3">
                    <button
                        type="button"
                        @click="agregarItems"
                        :disabled="saving || fetching || !leftSelected.length"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && !fetching && leftSelected.length)
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
                        :disabled="saving || fetching || !rightSelected.length"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && !fetching && rightSelected.length)
                            ? 'bg-rose-600 hover:bg-rose-700 text-white'
                            : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    >
                        <svg v-if="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <span>{{ saving ? 'Quitando...' : '← Quitar' }}</span>
                    </button>
                </div>

                <!-- Asignados -->
                <div class="lg:col-span-5 rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <div class="p-4 border-b border-white/10 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Asignados</h3>
                            <p class="text-xs text-slate-400">
                                {{ asignadosArray.length }} asignados · {{ rightSelected.length }} seleccionados
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAllAssigned"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Todo
                            </button>
                            <button
                                type="button"
                                @click="clearAssigned"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        <input
                            v-model="searchAssigned"
                            type="text"
                            placeholder="Buscar..."
                            class="w-full mb-3 rounded-lg bg-black/20 border border-white/10 text-white placeholder:text-slate-500 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                        />

                        <div class="max-h-[420px] overflow-auto pr-1 space-y-1">
                            <label
                                v-for="item in filteredAsignados"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="rightSelected"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40"
                                />
                                <span class="text-sm text-slate-200">
                                    {{ item.label }}
                                    <span class="text-xs text-slate-500">(#{{ item.id }})</span>
                                </span>
                            </label>

                            <div v-if="!filteredAsignados.length" class="text-sm text-slate-400 py-6 text-center">
                                No hay elementos asignados.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
