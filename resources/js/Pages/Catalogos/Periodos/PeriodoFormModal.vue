<script setup>
import { ref, watch, reactive, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editMode: {
        type: Boolean,
        default: false,
    },
    periodo: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:show', 'saved'])

const form = reactive({
    ano: '',
    mes: '',
    mes_nombre: '',
    tipo: 0,
    digito: 0,
    predeterminado: false,
})

const errors = ref({})

const titulo = computed(() =>
    props.editMode ? 'Editar periodo' : 'Nuevo periodo'
)

watch(
    () => props.show,
    (val) => {
        if (val) {
            initForm()
        }
    }
)

function initForm() {
    errors.value = {}

    if (props.editMode && props.periodo) {
        form.ano = props.periodo.ano ?? ''
        form.mes = props.periodo.mes ?? ''
        form.mes_nombre = props.periodo.mes_nombre ?? ''
        form.tipo = props.periodo.tipo ?? 0
        form.digito = props.periodo.digito ?? 0
        form.predeterminado = !!props.periodo.predeterminado
    } else {
        const now = new Date()
        form.ano = now.getFullYear()
        form.mes = now.getMonth() + 1
        form.mes_nombre = ''
        form.tipo = 0
        form.digito = 0
        form.predeterminado = false
    }
}

function close() {
    emit('update:show', false)
}

async function submit() {
    errors.value = {}

    const payload = {
        ano: Number(form.ano),
        mes: Number(form.mes),
        mes_nombre: form.mes_nombre,
        tipo: Number(form.tipo),
        digito: Number(form.digito),
        predeterminado: !!form.predeterminado,
    }

    try {
        let response
        if (props.editMode && props.periodo) {
            response = await axios.put(
                route('periodos.update', props.periodo.id),
                payload
            )
        } else {
            response = await axios.post(route('periodos.store'), payload)
        }

        const periodo = response.data.periodo

        Swal.fire(
            'Listo',
            props.editMode
                ? 'Periodo actualizado correctamente.'
                : 'Periodo creado correctamente.',
            'success'
        )

        emit('saved', periodo)
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors || {}
        } else {
            console.error(error)
            Swal.fire(
                'Error',
                'Ocurrió un error al guardar el periodo.',
                'error'
            )
        }
    }
}
</script>

<template>
    <!-- Overlay + modal. Cierra solo si se hace click FUERA del cuadro -->
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="close"
    >
        <div
            class="relative w-full max-w-lg mx-4 rounded-2xl bg-slate-900 border border-slate-700 shadow-2xl"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-slate-100 flex items-center gap-2">
                    <i class="fa-regular fa-calendar"></i>
                    {{ titulo }}
                </h2>

                <button
                    type="button"
                    class="text-slate-400 hover:text-white transition"
                    @click="close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submit" class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Año -->
                    <div>
                        <label class="block text-xs font-semibold mb-1">Año</label>
                        <input
                            v-model="form.ano"
                            type="number"
                            class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm focus:ring-2 focus:ring-alumun-pino focus:border-alumun-pino"
                        />
                        <p v-if="errors.ano" class="text-xs text-red-400 mt-1">
                            {{ errors.ano[0] }}
                        </p>
                    </div>

                    <!-- Mes -->
                    <div>
                        <label class="block text-xs font-semibold mb-1">Mes (1-12)</label>
                        <input
                            v-model="form.mes"
                            type="number"
                            min="1"
                            max="12"
                            class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm focus:ring-2 focus:ring-alumun-pino focus:border-alumun-pino"
                        />
                        <p v-if="errors.mes" class="text-xs text-red-400 mt-1">
                            {{ errors.mes[0] }}
                        </p>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-xs font-semibold mb-1">Tipo</label>
                        <input
                            v-model="form.tipo"
                            type="number"
                            min="0"
                            max="9"
                            class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm focus:ring-2 focus:ring-alumun-pino focus:border-alumun-pino"
                        />
                        <p v-if="errors.tipo" class="text-xs text-red-400 mt-1">
                            {{ errors.tipo[0] }}
                        </p>
                    </div>

                    <!-- Dígito -->
                    <div>
                        <label class="block text-xs font-semibold mb-1">Dígito</label>
                        <input
                            v-model="form.digito"
                            type="number"
                            min="0"
                            max="9"
                            class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm focus:ring-2 focus:ring-alumun-pino focus:border-alumun-pino"
                        />
                        <p v-if="errors.digito" class="text-xs text-red-400 mt-1">
                            {{ errors.digito[0] }}
                        </p>
                    </div>
                </div>

                <!-- Mes nombre -->
                <div>
                    <label class="block text-xs font-semibold mb-1">
                        Nombre de mes (opcional)
                    </label>
                    <input
                        v-model="form.mes_nombre"
                        type="text"
                        placeholder="ENERO, FEBRERO, etc. (si se deja vacío se calcula)"
                        class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm focus:ring-2 focus:ring-alumun-pino focus:border-alumun-pino"
                    />
                    <p v-if="errors.mes_nombre" class="text-xs text-red-400 mt-1">
                        {{ errors.mes_nombre[0] }}
                    </p>
                </div>

                <!-- Predeterminado -->
                <div class="flex items-center gap-2 mt-2">
                    <input
                        id="predeterminado"
                        v-model="form.predeterminado"
                        type="checkbox"
                        class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-alumun-pino focus:ring-alumun-pino"
                    />
                    <label for="predeterminado" class="text-sm text-slate-100">
                        Marcar como periodo predeterminado
                    </label>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-700 pt-4">
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-sm text-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg bg-alumun-pino hover:bg-emerald-600 text-sm text-white font-semibold shadow-lg"
                    >
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
