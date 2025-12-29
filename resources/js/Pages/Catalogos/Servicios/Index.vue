<script setup>
import {computed, ref} from "vue";
import axios from "axios";
import {Head, router} from "@inertiajs/vue3";
import ModalForm from "@/Components/Ui/ModalForm.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ActionButtons from "@/Components/Ui/ActionButtons.vue";
import DataTableCustom from "@/Components/Ui/DataTableCustom.vue";
import {confirmDanger} from "@/Composables/useConfirm.js";
import {toastSuccess} from "@/Composables/useToast.js";
import {useFormNumberInput} from "@/Composables/useFormNumberInput.js";
import {useFormNumberInputDirect} from "@/Composables/useFormNumberInputDirect.js";

const props = defineProps({
    servicios: Array,
    grupos: Array,
});

const errorsServicio = computed(() => {
    const e = {}

    const num = (v) => (v === '' || v === null || v === undefined ? null : Number(v))
    const isNum = (v) => v !== null && !Number.isNaN(v)
    const ge0 = (v) => isNum(v) && v >= 0

    const promCons = num(form.prom_consumo)
    const minCons  = num(form.prom_consumo_min)
    const maxCons  = num(form.prom_consumo_max)

    const promCost = num(form.prom_costo)
    const minCost  = num(form.prom_costo_min)
    const maxCost  = num(form.prom_costo_max)

    // required + number + min 0
    const reqNum = (key, v, label) => {
        if (v === null) e[key] = `${label} es requerido.`
        else if (!isNum(v)) e[key] = `${label} debe ser numérico.`
        else if (!ge0(v)) e[key] = `${label} no puede ser menor a 0.`
    }

    reqNum('prom_consumo', promCons, 'Prom. Consumo')
    reqNum('prom_consumo_min', minCons, 'Consumo Mín')
    reqNum('prom_consumo_max', maxCons, 'Consumo Máx')

    reqNum('prom_costo', promCost, 'Prom. Costo')
    reqNum('prom_costo_min', minCost, 'Costo Mín')
    reqNum('prom_costo_max', maxCost, 'Costo Máx')

    // reglas cruzadas: min <= prom <= max
    const between = (minKey, promKey, maxKey, minV, promV, maxV, label) => {
        if (isNum(minV) && isNum(maxV) && minV > maxV) {
            e[minKey] = `${label}: el mínimo no puede ser mayor al máximo.`
            e[maxKey] = `${label}: el máximo no puede ser menor al mínimo.`
        }

        if (isNum(minV) && isNum(promV) && promV < minV) {
            e[promKey] = `${label}: el promedio no puede ser menor al mínimo.`
        }

        if (isNum(maxV) && isNum(promV) && promV > maxV) {
            e[promKey] = `${label}: el promedio no puede ser mayor al máximo.`
        }
    }

    between('prom_consumo_min', 'prom_consumo', 'prom_consumo_max', minCons, promCons, maxCons, 'Consumo')
    between('prom_costo_min', 'prom_costo', 'prom_costo_max', minCost, promCost, maxCost, 'Costo')

    return e
})

const hasErrorsServicio = computed(() => Object.keys(errorsServicio.value).length > 0)


const form = ref({
    id: null,
    rpu: "",
    medidor: "",
    cuenta: "",
    tarifa: "",
    carga_contratada: "",
    carga_conectada: "",
    carga_minima: 0.00,
    carga_maxima: 0.00,
    prom_consumo: 0.00,
    prom_consumo_min: 0.00,
    prom_consumo_max: 0.00,
    prom_costo: 0.00,
    prom_costo_min: 0.00,
    prom_costo_max: 0.00,
    rmu: "",
    direccion: "",
    ciudad: "",
    colonia: "",
    calle_1: "",
    calle_2: "",
    calle_3: "",
    alias: "",
    grupo_id: null,
});

const errors = ref({});
const modalOpen = ref(false);
const editMode = ref(false);

const showSelection = ref(false);
const isColActions = ref(true);
const activeTab = ref("general");

function openCreate() {
    form.value = {
        id: null,
        rpu: "", medidor: "", cuenta: "", tarifa: "",
        carga_contratada: "", carga_conectada: "", rmu: "",
        direccion: "", ciudad: "", colonia: "",
        calle_1: "", calle_2: "", calle_3: "",
        alias: "", grupo_id: null,
        carga_minima: 0.00, carga_maxima: 0.00,
        prom_consumo: 0.00, prom_consumo_min: 0.00, prom_consumo_max: 0.00,
        prom_costo: 0.00, prom_costo_min: 0.00, prom_costo_max: 0.00,

    };

    errors.value = {};
    editMode.value = false;
    modalOpen.value = true;
}

function openEdit(s) {
    form.value = { ...s };
    errors.value = {};
    editMode.value = true;
    modalOpen.value = true;
}

async function submit() {
    errors.value = {};

    try {
        if (editMode.value) {
            // await axios.put(route("servicios.update", form.value.id), form.value);
            await axios.post(route('servicios.update', form.value.id), {
                ...form.value,
                _method: 'PUT'
            });
            toastSuccess("Servicio actualizado correctamente");
        } else {
            await axios.post(route("servicios.store"), form.value);
            toastSuccess("Servicio agregado correctamente");
        }

        router.reload({ only: ["servicios"] });
        modalOpen.value = false;

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    }
}

async function destroyItem(s) {

    const ok = await confirmDanger(
        "Eliminar servicio",
        `¿Seguro que deseas eliminar el servicio #${s.id} (${s.rpu})?`,
    );
    if (!ok) return;


    // await axios.delete(route("servicios.destroy", s.id));
    await axios.post(route('servicios.destroy', s.id), {
        ...form.value,
        _method: 'DELETE'
    });
    window.dispatchEvent(new CustomEvent("toast", {
        detail: { type: "success", message: "Servicio eliminado" }
    }));
    router.reload({ only: ["servicios"] });
}



// Crear los inputs sincronizados (MUY SIMPLE 🎯)
const cargaConectada = useFormNumberInputDirect(form, 'carga_conectada', {
    maxInteger: 12,
    maxDecimal: 4
})

const cargaContratada = useFormNumberInputDirect(form, 'carga_contratada', {
    maxInteger: 6,
    maxDecimal: 2
})

const cargaMinima = useFormNumberInputDirect(form, 'carga_minima', {
    maxInteger: 8,
    maxDecimal: 0
})

const cargaMaxima = useFormNumberInputDirect(form, 'carga_maxima', {
    maxInteger: 3,
    maxDecimal: 1
})

// Validación del formulario completo
const isFormValid = computed(() => {
    return cargaConectada.isValid &&
        cargaContratada.isValid &&
        cargaMinima.isValid &&
        cargaMaxima.isValid
})

// si ya tienes `errors` definido, no lo dupliques.
// ejemplo típico: const errors = usePage().props.errors

const fieldError = (key) => {
    const server = errors?.[key]
    const serverMsg = Array.isArray(server) ? server[0] : server
    const clientMsg = errorsServicio?.value?.[key] // si estás usando el validador que te di
    return serverMsg || clientMsg || ''
}


</script>

<template>
    <AuthenticatedLayout>
        <template #title>Calálogo de Servicios</template>

        <div class="p-0">
            <button
                @click="openCreate"
                class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 mb-2"
            >
                + Nuevo Servicio
            </button>

            <DataTableCustom
                title="Listado de Servicios"
                :items="props.servicios"
                :columns="[
                    { label: '🆔 ID', field: 'id', sortable: true },
                    { label: '🎟️ RPU', field: 'rpu', sortable: true },
                    { label: '📅 Medidor', field: 'medidor', sortable: true },
                    { label: '👨‍👩‍👧 Cuenta', field: 'cuenta', sortable: true },
                    { label: '🔢 Prom Consumo', field: 'prom_consumo', align: 'text-right', sortable: true },
                    { label: '💰 Prom Costo', field: 'prom_costo', align: 'text-right', sortable: true },
                    { label: '🏦 Dirección', field: 'direccion', align: 'text-left', sortable: true },
                    { label: '🏦 Colonia', field: 'colonia', align: 'text-left', sortable: true },
                    { label: 'Contratdada', field: 'carga_contratada', align: 'text-left', sortable: true }
                  ]"
                :showSelection="showSelection"
                :isColActions="isColActions"
                paginationMode="items"
                :footerSummary="true"
                :summaryFields="['prom_consumo','prom_costo']"
                summary-column="prom_consumo"

            >
                <template #actions="{ item }">
                    <ActionButtons
                        :onEdit="() => openEdit(item)"
                        :onDelete="() => destroyItem(item)"
                    />
                </template>
            </DataTableCustom>


            <!-- Modal -->
            <ModalForm
                v-model="modalOpen"
                size="xl"
                :title="editMode ? 'Editar Servicio' : 'Nuevo Servicio'"
                @submit="submit"
            >
                <template #default>

                    <!-- TABS -->
                    <div class="flex gap-3 mb-6">
                        <!-- TAB GENERAL -->
                        <Transition name="tab-slide">
                                 <button
                                        @click="activeTab = 'general'"
                                        class="px-4 py-2 rounded-full transition font-semibold shadow-sm border cursor-pointer"
                                        :class="activeTab === 'general'
                                ? 'bg-sky-200 text-sky-900 border-sky-300'
                                : 'bg-slate-200 text-slate-600 border-slate-300'"
                                    >
                                        🌟 General
                                    </button>
                        </Transition>
                        <!-- TAB CARGA -->
                        <Transition name="tab-slide">
                                <button
                                    @click="activeTab = 'carga'"
                                    class="px-4 py-2 rounded-full transition font-semibold shadow-sm border cursor-pointer"
                                    :class="activeTab === 'carga'
                            ? 'bg-emerald-200 text-emerald-900 border-emerald-300'
                            : 'bg-slate-200 text-slate-600 border-slate-300'"
                                >
                                    ⚡ Carga
                                </button>
                        </Transition>
                        <!-- TAB DOMICILIO -->
                        <Transition name="tab-slide">
                            <button
                                @click="activeTab = 'domicilio'"
                                class="px-4 py-2 rounded-full transition font-semibold shadow-sm border cursor-pointer"
                                :class="activeTab === 'domicilio'
                            ? 'bg-emerald-200 text-emerald-900 border-emerald-300'
                            : 'bg-slate-200 text-slate-600 border-slate-300'"
                            >
                                🏡 Domicilio
                            </button>
                        </Transition>
                        <!-- TAB PROMEDIOS -->
                        <Transition name="tab-slide">
                            <button
                                @click="activeTab = 'promedio'"
                                class="px-4 py-2 rounded-full transition font-semibold shadow-sm border cursor-pointer"
                                :class="activeTab === 'promedio'
                            ? 'bg-emerald-200 text-emerald-900 border-emerald-300'
                            : 'bg-slate-200 text-slate-600 border-slate-300'"
                            >
                                🔢 Promedios
                            </button>
                        </Transition>

                    </div>

                    <!-- TAB GENERAL -->
                    <div v-if="activeTab === 'general'" class="grid grid-cols-2 gap-6">

                        <!-- RPU -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">RPU</label>
                            <div class="relative">
                                <span class="emoji-icon">🔢</span>
                                <input v-model="form.rpu" class="input-field" />
                            </div>
                            <p v-if="errors.rpu" class="text-sm text-rose-500">{{ errors.rpu[0] }}</p>
                        </div>

                        <!-- Medidor -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Medidor</label>
                            <div class="relative">
                                <span class="emoji-icon">🔌</span>
                                <input v-model="form.medidor" class="input-field" />
                            </div>
                            <p v-if="errors.medidor" class="text-sm text-rose-500">{{ errors.medidor[0] }}</p>
                        </div>

                        <!-- Cuenta -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Cuenta</label>
                            <div class="relative">
                                <span class="emoji-icon">💳</span>
                                <input v-model="form.cuenta" class="input-field" />
                            </div>
                            <p v-if="errors.cuenta" class="text-sm text-rose-500">{{ errors.cuenta[0] }}</p>
                        </div>

                        <!-- Tarifa -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Tarifa</label>
                            <div class="relative">
                                <span class="emoji-icon">💵</span>
                                <input v-model="form.tarifa" class="input-field" />
                            </div>
                            <p v-if="errors.tarifa" class="text-sm text-rose-500">{{ errors.tarifa[0] }}</p>
                        </div>

                        <!-- RMU -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">RMU</label>
                            <div class="relative">
                                <span class="emoji-icon">🏷️</span>
                                <input v-model="form.rmu" class="input-field" />
                            </div>
                            <p v-if="errors.rmu" class="text-sm text-rose-500">{{ errors.rmu[0] }}</p>
                        </div>

                        <!-- Grupo -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Grupo</label>
                            <div class="relative">
                                <span class="emoji-icon">🗂️</span>
                                <select v-model="form.grupo_id" class="input-field">
                                    <option :value="null">Sin grupo</option>
                                    <option v-for="g in props.grupos" :key="g.id" :value="g.id">{{ g.grupo }}</option>
                                </select>
                            </div>
                            <p v-if="errors.grupo_id" class="text-sm text-rose-500">{{ errors.grupo_id[0] }}</p>
                        </div>

                    </div>

                    <!-- TAB CARGA -->
                    <div v-else-if="activeTab === 'carga'" class="grid grid-cols-2 gap-6">

                        <!-- Carga contratada -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Carga Contratada</label>
                            <div class="relative">
                                <span class="emoji-icon">⚡</span>
                                <input
                                    v-model="form.carga_contratada"
                                    class="input-field"
                                    type="number"
                                    placeholder="12 enteros, 4 decimales"
                                    :class="{ 'error': !cargaContratada.isValid }"
                                />
                            </div>
                            <p v-if="errors.carga_contratada" class="text-sm text-rose-500">{{ errors.carga_contratada[0] }}</p>
                        </div>

                        <!-- Carga conectada -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Carga Conectada</label>
                            <div class="relative">
                                <span class="emoji-icon">🔋</span>
                                <input
                                    v-model="form.carga_conectada"
                                    class="input-field"
                                    type="text"
                                    placeholder="12 enteros, 4 decimales"
                                    :class="{ 'error': !cargaConectada.isValid }"
                                />
                            </div>
                            <p v-if="errors.carga_conectada" class="text-sm text-rose-500">{{ errors.carga_conectada[0] }}</p>
                        </div>

                        <!-- Carga Minima -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Carga Mínima</label>
                            <div class="relative">
                                <span class="emoji-icon">⚡</span>
                                <input
                                    v-model="form.carga_minima"
                                    class="input-field"
                                    type="text"
                                    placeholder="12 enteros, 4 decimales"
                                    :class="{ 'error': !cargaMinima.isValid }"
                                />
                            </div>
                            <p v-if="errors.carga_minima" class="text-sm text-rose-500">{{ errors.carga_minima[0] }}</p>
                        </div>

                        <!-- Carga Maxima -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Carga Máxima</label>
                            <div class="relative">
                                <span class="emoji-icon">🔋</span>
                                <input
                                    v-model="form.carga_maxima"
                                    class="input-field"
                                    type="text"
                                    placeholder="12 enteros, 4 decimales"
                                    :class="{ 'error': !cargaMaxima.isValid }"
                                />
                            </div>
                            <p v-if="errors.carga_maxima" class="text-sm text-rose-500">{{ errors.carga_maxima[0] }}</p>
                        </div>



                    </div>

                    <!-- TAB DOMICILIO -->
                    <div v-else-if="activeTab === 'domicilio'" class="grid grid-cols-2 gap-6">

                        <!-- Dirección -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Dirección completa</label>
                            <div class="relative">
                                <span class="emoji-icon">📍</span>
                                <input v-model="form.direccion" class="input-field" />
                            </div>
                            <p v-if="errors.direccion" class="text-sm text-rose-500">{{ errors.direccion[0] }}</p>
                        </div>

                        <!-- Ciudad -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Ciudad</label>
                            <div class="relative">
                                <span class="emoji-icon">🏙️</span>
                                <input v-model="form.ciudad" class="input-field" />
                            </div>
                            <p v-if="errors.ciudad" class="text-sm text-rose-500">{{ errors.ciudad[0] }}</p>
                        </div>

                        <!-- Colonia -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Colonia</label>
                            <div class="relative">
                                <span class="emoji-icon">🏠</span>
                                <input v-model="form.colonia" class="input-field" />
                            </div>
                            <p v-if="errors.colonia" class="text-sm text-rose-500">{{ errors.colonia[0] }}</p>
                        </div>

                        <!-- Calle 1 -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Calle 1</label>
                            <div class="relative">
                                <span class="emoji-icon">🛣️</span>
                                <input v-model="form.calle_1" class="input-field" />
                            </div>
                            <p v-if="errors.calle_1" class="text-sm text-rose-500">{{ errors.calle_1[0] }}</p>
                        </div>

                        <!-- Calle 2 -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Calle 2</label>
                            <div class="relative">
                                <span class="emoji-icon">🛤️</span>
                                <input v-model="form.calle_2" class="input-field" />
                            </div>
                        </div>

                        <!-- Calle 3 -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Calle 3</label>
                            <div class="relative">
                                <span class="emoji-icon">🚏</span>
                                <input v-model="form.calle_3" class="input-field" />
                            </div>
                        </div>

                        <!-- Alias -->
                        <div>
                            <label class="block mb-1 font-medium text-slate-200">Alias</label>
                            <div class="relative">
                                <span class="emoji-icon">💬</span>
                                <input v-model="form.alias" class="input-field" />
                            </div>
                        </div>

                    </div>

                    <!-- TAB PROMEDIOS -->
                    <div v-else class="grid grid-cols-6 gap-6">
                        <!-- prom_consumo -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Prom. Consumo</label>
                            <div class="relative">
                                <span class="emoji-icon">⚡</span>
                                <input
                                    v-model.number="form.prom_consumo"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_consumo')" class="text-sm text-rose-500">
                                {{ fieldError('prom_consumo') }}
                            </p>
                        </div>

                        <!-- prom_consumo_min -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Consumo Mín</label>
                            <div class="relative">
                                <span class="emoji-icon">📉</span>
                                <input
                                    v-model.number="form.prom_consumo_min"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_consumo_min')" class="text-sm text-rose-500">
                                {{ fieldError('prom_consumo_min') }}
                            </p>
                        </div>

                        <!-- prom_consumo_max -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Consumo Máx</label>
                            <div class="relative">
                                <span class="emoji-icon">📈</span>
                                <input
                                    v-model.number="form.prom_consumo_max"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_consumo_max')" class="text-sm text-rose-500">
                                {{ fieldError('prom_consumo_max') }}
                            </p>
                        </div>

                        <!-- prom_costo -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Prom. Costo</label>
                            <div class="relative">
                                <span class="emoji-icon">💰</span>
                                <input
                                    v-model.number="form.prom_costo"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_costo')" class="text-sm text-rose-500">
                                {{ fieldError('prom_costo') }}
                            </p>
                        </div>

                        <!-- prom_costo_min -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Costo Mín</label>
                            <div class="relative">
                                <span class="emoji-icon">🧾</span>
                                <input
                                    v-model.number="form.prom_costo_min"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_costo_min')" class="text-sm text-rose-500">
                                {{ fieldError('prom_costo_min') }}
                            </p>
                        </div>

                        <!-- prom_costo_max -->
                        <div class="col-span-2">
                            <label class="block mb-1 font-medium text-slate-200">Costo Máx</label>
                            <div class="relative">
                                <span class="emoji-icon">🏷️</span>
                                <input
                                    v-model.number="form.prom_costo_max"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="input-field"
                                />
                            </div>
                            <p v-if="fieldError('prom_costo_max')" class="text-sm text-rose-500">
                                {{ fieldError('prom_costo_max') }}
                            </p>
                        </div>
                    </div>

                </template>
            </ModalForm>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
button {
    transition: transform 150ms ease;
}

button:active {
    transform: scale(0.96);
}
</style>
