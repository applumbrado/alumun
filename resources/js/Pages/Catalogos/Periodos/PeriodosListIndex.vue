<script setup>
import { router } from '@inertiajs/vue3'

import {ref, watch} from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DataTableCustom from '@/Components/Ui/DataTableCustom.vue'
import PeriodoFormModal from "@/Pages/Catalogos/Periodos/PeriodoFormModal.vue";
import {usePeriodoVigente} from "@/Composables/usePeriodoVigente.js";

const { periodoVigente } = usePeriodoVigente()

const props = defineProps({
    periodos: {
        type: Array,
        default: () => [],
    },
})

const periodos = ref([...props.periodos])

const isModalOpen = ref(false)
const editMode = ref(false)
const selectedPeriodo = ref(null)

const columns = [
    { field: 'anomes',        label: 'AñoMes',        sortable: true },
    { field: 'ano',           label: 'Año',           sortable: true, align: 'text-center' },
    { field: 'mes',           label: 'Mes',           sortable: true, align: 'text-center' },
    { field: 'mes_nombre',    label: 'Mes nombre',    sortable: true },
    { field: 'tipo',          label: 'Tipo',          sortable: true, align: 'text-center' },
    { field: 'digito',        label: 'Dígito',        sortable: true, align: 'text-center' },
    { field: 'predeterminado',label: 'Predeterminado',sortable: true, align: 'text-center' },
    { field: 'label',         label: 'Etiqueta',      sortable: false },
]

function openCreate() {
    editMode.value = false
    selectedPeriodo.value = null
    isModalOpen.value = true
}

function openEdit(item) {
    editMode.value = true
    selectedPeriodo.value = { ...item }
    isModalOpen.value = true
}

function handleSaved(periodo) {
    // Actualiza o inserta en el array local
    const idx = periodos.value.findIndex(p => p.id === periodo.id)
    if (idx === -1) {
        periodos.value.push(periodo)
    } else {
        periodos.value[idx] = periodo
    }

    // Reordenar por año/mes/tipo
    periodos.value.sort((a, b) => {
        if (a.ano !== b.ano) return b.ano - a.ano
        if (a.mes !== b.mes) return b.mes - a.mes
        return a.tipo - b.tipo
    })

    isModalOpen.value = false
}

async function deletePeriodo(item) {
    const result = await Swal.fire({
        title: '¿Eliminar periodo?',
        text: `${item.label}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c',
    })

    if (!result.isConfirmed) return

    try {
        await axios.delete(route('periodos.destroy', item.id))

        periodos.value = periodos.value.filter(p => p.id !== item.id)

        Swal.fire('Eliminado', 'El periodo ha sido eliminado.', 'success')
    } catch (error) {
        console.error(error)
        Swal.fire('Error', 'No se pudo eliminar el periodo.', 'error')
    }
}


async function setPredeterminado(item) {
    try {
        const { data } = await axios.post(route('periodos.predeterminar', item.id))

        const updated = data.periodo

        // 🔹 1) Actualizamos la tabla local
        periodos.value = periodos.value.map(p => ({
            ...p,
            predeterminado: p.id === updated.id,
        }))

        // 🔹 2) Refrescamos SOLO el prop compartido "periodo_vigente"
        router.reload({
            only: ['periodo_vigente'],
            preserveScroll: true,
        })

        Swal.fire('Listo', 'Periodo marcado como predeterminado.', 'success')
    } catch (error) {
        console.error(error)
        Swal.fire('Error', 'No se pudo marcar como predeterminado.', 'error')
    }
}

watch(
    periodoVigente,
    (nuevo) => {
        if (!nuevo) return

        periodos.value = periodos.value.map(p => ({
            ...p,
            predeterminado: p.id === nuevo.id,
        }))
    },
    { immediate: false } // 👈 para que al montar ya quede sincronizado
)



</script>

<template>
    <AuthenticatedLayout>
        <Head title="Periodos" />

        <div class="py-6 px-4 md:px-8">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="fa-regular fa-calendar-days text-alumun-pino"></i>
                    Periodos
                </h1>

                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 bg-alumun-pino hover:bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg transition text-sm font-semibold"
                >
                    <i class="fa-solid fa-plus"></i>
                    Nuevo periodo
                </button>
            </div>

            <DataTableCustom
                :items="periodos"
                :columns="columns"
                :pagination-mode="'items'"
                :show-day-pagination="false"
                :show-selection="false"
                :is-col-actions="true"
                :show-export="true"
            >
                <!-- Columna Predeterminado -->
                <template #predeterminado="{ item }">
                    <span
                        v-if="item.predeterminado"
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/40"
                    >
                        <i class="fa-solid fa-star mr-1 text-amber-300"></i>
                        Sí
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-slate-700/40 text-slate-300 border border-slate-500/40"
                    >
                        No
                    </span>
                </template>

                <!-- Columna de acciones -->
                <template #actions="{ item }">
                    <div class="flex items-center justify-center gap-2">
                        <!-- Editar -->
                        <button
                            @click.stop="openEdit(item)"
                            class="p-1.5 rounded-full bg-blue-500/20 hover:bg-blue-500/40 text-blue-300 hover:text-white transition"
                            title="Editar"
                        >
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                        </button>

                        <!-- Predeterminar -->
                        <button
                            @click.stop="setPredeterminado(item)"
                            class="p-1.5 rounded-full bg-amber-500/20 hover:bg-amber-500/40 text-amber-300 hover:text-white transition"
                            :title="item.predeterminado ? 'Es predeterminado' : 'Fijar como predeterminado'"
                        >
                            <i
                                class="fa-solid fa-star text-xs"
                                :class="item.predeterminado ? 'animate-pulse' : ''"
                            ></i>
                        </button>

                        <!-- Eliminar -->
                        <button
                            @click.stop="deletePeriodo(item)"
                            class="p-1.5 rounded-full bg-red-500/20 hover:bg-red-500/40 text-red-300 hover:text-white transition"
                            title="Eliminar"
                        >
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </template>
            </DataTableCustom>
        </div>

        <PeriodoFormModal
            v-model:show="isModalOpen"
            :edit-mode="editMode"
            :periodo="selectedPeriodo"
            @saved="handleSaved"
        />
    </AuthenticatedLayout>
</template>
