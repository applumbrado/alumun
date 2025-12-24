<script setup>
import { ref, computed, reactive, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import { Head } from "@inertiajs/vue3"

const props = defineProps({
    users: Array,
    allDatas: Array,
    assignedDatasByUser: Object,
    tableName: String,
    addUrl: String,
    removeUrl: String,
    totalAlumnos: Number
})

const selectedUserId = ref(null)
const selectedAvailable = ref([])
const selectedAssigned = ref([])

const localAssignedDatas = reactive({
    ...(props.assignedDatasByUser ?? {})
})

// UX: búsquedas rápidas (sin jQuery/select2)
const userQuery = ref('')
const searchAvailable = ref('')
const searchAssigned = ref('')
const saving = ref(false)

// Combobox users (reemplaza select)
const userBoxRef = ref(null)
const userDropdownOpen = ref(false)
const userHighlight = ref(0)

const usersList = computed(() => props.users ?? [])
const allDatasList = computed(() => props.allDatas ?? [])

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
        .replace(/[\u0300-\u036f]/g, '') // sin acentos
        .replace(/\s+/g, ' ')
        .trim()
}

const userLabel = (u) => {
    if (!u) return ''
    return u.full_name || u.name || u.username || u.email || `Usuario #${u.id}`
}

const userSearchText = (u) => {
    return normalize([
        userLabel(u),
        u?.username ?? '',
        u?.email ?? '',
        u?.id ?? ''
    ].join(' '))
}

const selectedUser = computed(() => {
    if (!selectedUserId.value) return null
    return usersList.value.find(u => String(u.id) === String(selectedUserId.value)) ?? null
})

const assignedDatas = computed(() => {
    return selectedUserId.value
        ? localAssignedDatas[selectedUserId.value] || []
        : []
})

const availableDatas = computed(() => {
    const assignedIds = assignedDatas.value.map(d => d.id)
    return allDatasList.value.filter(d => !assignedIds.includes(d.id))
})

const initialAssignedByUser = computed(() => {
    const src = props.assignedDatasByUser ?? {}
    const out = {}
    Object.keys(src).forEach((userId) => {
        out[userId] = (src[userId] ?? []).map(x => x.id)
    })
    return out
})

const diffChanges = computed(() => {
    if (!selectedUserId.value) return { added: 0, removed: 0, addedIds: [], removedIds: [] }

    const uid = String(selectedUserId.value)
    const initial = new Set(initialAssignedByUser.value[uid] ?? [])
    const current = new Set((assignedDatas.value ?? []).map(x => x.id))

    const addedIds = [...current].filter(id => !initial.has(id))
    const removedIds = [...initial].filter(id => !current.has(id))

    return {
        added: addedIds.length,
        removed: removedIds.length,
        addedIds,
        removedIds,
    }
})

// ✅ Combobox filtrado (limitado)
const filteredUsers = computed(() => {
    const q = normalize(userQuery.value)
    const base = usersList.value

    if (!q) return base.slice(0, 50)

    const out = base.filter(u => userSearchText(u).includes(q))
    return out.slice(0, 100)
})

const filteredAvailableDatas = computed(() => {
    const q = normalize(searchAvailable.value)
    if (!q) return availableDatas.value

    return availableDatas.value.filter(item => {
        const label = normalize(item.data ?? item.name ?? '')
        return label.includes(q) || String(item.id).includes(q)
    })
})

const filteredAssignedDatas = computed(() => {
    const q = normalize(searchAssigned.value)
    if (!q) return assignedDatas.value

    return assignedDatas.value.filter(item => {
        const label = normalize(item.data ?? item.name ?? '')
        return label.includes(q) || String(item.id).includes(q)
    })
})

// --- Combobox handlers ---
function openUserDropdown() {
    userDropdownOpen.value = true
    userHighlight.value = 0
}
function closeUserDropdown() {
    userDropdownOpen.value = false
}
function chooseUser(u) {
    selectedUserId.value = u.id
    userQuery.value = userLabel(u)
    closeUserDropdown()
}
function onUserKeydown(e) {
    if (!userDropdownOpen.value && (e.key === 'ArrowDown' || e.key === 'Enter')) {
        openUserDropdown()
        return
    }

    const list = filteredUsers.value
    if (!list.length) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        userHighlight.value = Math.min(userHighlight.value + 1, list.length - 1)
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        userHighlight.value = Math.max(userHighlight.value - 1, 0)
    } else if (e.key === 'Enter') {
        e.preventDefault()
        chooseUser(list[userHighlight.value])
    } else if (e.key === 'Escape') {
        e.preventDefault()
        closeUserDropdown()
    } else {
        // cualquier tecla de escritura abre dropdown
        openUserDropdown()
    }
}
function onDocClick(ev) {
    if (!userBoxRef.value) return
    if (!userBoxRef.value.contains(ev.target)) closeUserDropdown()
}

onMounted(() => document.addEventListener('click', onDocClick, true))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick, true))

// --- Actions ---
const addDatas = async () => {
    if (!selectedUserId.value || !selectedAvailable.value.length || saving.value) return

    saving.value = true
    try {
        const { data } = await axios.post(props.addUrl, {
            user_id: selectedUserId.value,
            datas: selectedAvailable.value
        })

        localAssignedDatas[selectedUserId.value] = data.data[selectedUserId.value]
        selectedAvailable.value = []

        toast.fire({ icon: 'success', title: 'Asignados correctamente' })
    } catch (error) {
        const msg = error?.response?.data?.message || error.message || 'Error desconocido'
        toast.fire({ icon: 'error', title: msg })
    } finally {
        saving.value = false
    }
}

const removeDatas = async () => {
    if (!selectedUserId.value || !selectedAssigned.value.length || saving.value) return

    saving.value = true
    try {
        const { data } = await axios.post(props.removeUrl, {
            user_id: selectedUserId.value,
            datas: selectedAssigned.value
        })

        localAssignedDatas[selectedUserId.value] = data.data[selectedUserId.value]
        selectedAssigned.value = []

        toast.fire({ icon: 'success', title: 'Removidos correctamente' })
    } catch (error) {
        const msg = error?.response?.data?.message || error.message || 'Error desconocido'
        toast.fire({ icon: 'error', title: msg })
    } finally {
        saving.value = false
    }
}

const selectAllAvailable = () => {
    selectedAvailable.value = filteredAvailableDatas.value.map(x => x.id)
}
const clearAvailable = () => (selectedAvailable.value = [])

const selectAllAssigned = () => {
    selectedAssigned.value = filteredAssignedDatas.value.map(x => x.id)
}
const clearAssigned = () => (selectedAssigned.value = [])

watch(selectedUserId, async () => {
    selectedAvailable.value = []
    selectedAssigned.value = []
    searchAvailable.value = ''
    searchAssigned.value = ''

    await nextTick()
    if (selectedUser.value) {
        userQuery.value = userLabel(selectedUser.value)
    }
})
</script>

<template>
    <Head :title="`Asignación de ${tableName}`" />
    <AuthenticatedLayout>
        <template #title>
            Asignación de {{ tableName }}
        </template>

        <div class="p-6 rounded-xl border border-white/10 bg-black/30 shadow">
            <div class="flex flex-col gap-1 mb-6">
                <h1 class="text-xl font-bold text-white">
                    Asignación masiva de {{ tableName }}
                </h1>
                <p class="text-sm text-slate-300">
                    Selecciona un usuario y mueve elementos entre disponibles y asignados.
                    <span v-if="totalAlumnos" class="text-slate-400">(Total alumnos: {{ totalAlumnos }})</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Buscador / selector (Combobox) -->
                <div class="md:col-span-3" ref="userBoxRef">
                    <label class="block text-sm font-medium text-slate-200 mb-2">
                        Usuario
                    </label>

                    <div class="relative">
                        <input
                            v-model="userQuery"
                            @focus="openUserDropdown"
                            @keydown="onUserKeydown"
                            type="text"
                            placeholder="Escribe nombre, usuario o correo..."
                            class="w-full rounded-lg bg-black/20 border border-white/10 text-white placeholder:text-slate-500 px-3 py-2 pr-10
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                        />

                        <div class="absolute inset-y-0 right-2 flex items-center text-slate-400 pointer-events-none">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <div
                            v-if="userDropdownOpen"
                            class="absolute z-30 mt-2 w-full rounded-xl border border-white/10 bg-black/90 backdrop-blur shadow-lg overflow-hidden"
                        >
                            <div class="max-h-[320px] overflow-auto">
                                <button
                                    v-for="(u, idx) in filteredUsers"
                                    :key="u.id"
                                    type="button"
                                    @click="chooseUser(u)"
                                    @mouseenter="userHighlight = idx"
                                    class="w-full text-left px-3 py-2 text-sm flex items-center justify-between"
                                    :class="idx === userHighlight ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/5'"
                                >
                                    <span class="truncate">
                                        {{ userLabel(u) }}
                                    </span>
                                    <span class="ml-3 text-xs text-slate-500">#{{ u.id }}</span>
                                </button>

                                <div v-if="!filteredUsers.length" class="px-3 py-4 text-sm text-slate-400">
                                    Sin resultados. Ajusta tu búsqueda.
                                </div>
                            </div>

                            <div class="px-3 py-2 border-t border-white/10 text-xs text-slate-400">
                                Mostrando {{ filteredUsers.length }} de {{ usersList.length }} usuarios
                                <span v-if="normalize(userQuery).length < 2" class="text-slate-500">
                                    · escribe 2+ letras para filtrar mejor
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedUserId" class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2 py-1 rounded bg-white/5 border border-white/10 text-slate-300">
                            Seleccionado:
                            <span class="text-white">{{ userLabel(selectedUser) }}</span>
                        </span>

                        <span class="px-2 py-1 rounded bg-white/5 border border-white/10 text-slate-300">
                            Cambios:
                            <span class="text-emerald-300">+{{ diffChanges.added }}</span>
                            <span class="text-rose-300">-{{ diffChanges.removed }}</span>
                        </span>

                        <span v-if="saving" class="text-slate-400">
                            Guardando...
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="selectedUserId" class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                <!-- Disponibles -->
                <div class="lg:col-span-5 rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <div class="p-4 border-b border-white/10 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">
                                {{ tableName }} disponibles
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ availableDatas.length }} disponibles · {{ selectedAvailable.length }} seleccionados
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAllAvailable"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Seleccionar todo
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
                                v-for="item in filteredAvailableDatas"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="selectedAvailable"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40"
                                />
                                <span class="text-sm text-slate-200">
                                    {{ item.data ?? item.name }}
                                    <span class="text-xs text-slate-500">(#{{ item.id }})</span>
                                </span>
                            </label>

                            <div v-if="!filteredAvailableDatas.length" class="text-sm text-slate-400 py-6 text-center">
                                No hay elementos disponibles.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones mover -->
                <div class="lg:col-span-2 flex flex-col items-stretch justify-center gap-3">
                    <button
                        type="button"
                        @click="addDatas"
                        :disabled="saving || !selectedAvailable.length"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && selectedAvailable.length)
                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                            : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                    >
                        <svg v-if="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <span>{{ saving ? 'Asignando...' : 'Agregar →' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="removeDatas"
                        :disabled="saving || !selectedAssigned.length"
                        class="w-full px-4 py-2 rounded-lg font-semibold transition border border-white/10 flex items-center justify-center gap-2"
                        :class="(!saving && selectedAssigned.length)
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

                <!-- Asignados -->
                <div class="lg:col-span-5 rounded-xl border border-white/10 bg-black/20 overflow-hidden">
                    <div class="p-4 border-b border-white/10 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">
                                {{ tableName }} asignados
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ assignedDatas.length }} asignados · {{ selectedAssigned.length }} seleccionados
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAllAssigned"
                                class="px-3 py-1.5 text-xs rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                            >
                                Seleccionar todo
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
                                v-for="item in filteredAssignedDatas"
                                :key="item.id"
                                class="flex items-center gap-3 w-full cursor-pointer px-3 py-2 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="selectedAssigned"
                                    class="h-4 w-4 rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40"
                                />
                                <span class="text-sm text-slate-200">
                                    {{ item.data ?? item.name }}
                                    <span class="text-xs text-slate-500">(#{{ item.id }})</span>
                                </span>
                            </label>

                            <div v-if="!filteredAssignedDatas.length" class="text-sm text-slate-400 py-6 text-center">
                                No hay elementos asignados.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-sm text-slate-400 py-10 text-center border border-dashed border-white/10 rounded-xl bg-black/10">
                Selecciona un usuario para ver disponibles y asignados.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
