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
        carga_minima: 0.00, carga_maxima: 0.00
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
                    { label: '🏦 Tarifa', field: 'tarifa', align: 'text-right', sortable: true },
                    { label: '🏦 RMU', field: 'rmu', sortable: true },
                    { label: '💰 Dirección', field: 'direccion', align: 'text-left', sortable: true },
                    { label: '💰 Colonia', field: 'colonia', align: 'text-left', sortable: true },
                    { label: 'Contratdada', field: 'carga_contratada', align: 'text-left', sortable: true }
                  ]"
                :showSelection="showSelection"
                :isColActions="isColActions"
                paginationMode="items"
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
                    <div v-else class="grid grid-cols-2 gap-6">

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
