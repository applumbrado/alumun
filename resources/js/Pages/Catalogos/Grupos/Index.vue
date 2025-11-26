<script setup>
import { ref } from "vue";
import axios from "axios";
import ModalForm from "@/Components/Ui/ModalForm.vue";
import {Head, router, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ActionButtons from "@/Components/Ui/ActionButtons.vue";
import DataTableCustom from "@/Components/Ui/DataTableCustom.vue";
import {confirmDanger} from "@/Composables/useConfirm.js";
import {toastSuccess} from "@/Composables/useToast.js";

const props = defineProps({
    grupos: Array,
});

const errors = ref({});
const modalOpen = ref(false);
const editMode = ref(false);

const showSelection = ref(false);
const isColActions = ref(true);

const form = ref({
    id: null,
    grupo: "",
    clave: "",
});

function openCreate() {
    form.value = { id: null, grupo: "", clave: "" };
    errors.value = {};
    editMode.value = false;
    modalOpen.value = true;
}

function openEdit(g) {
    form.value = { ...g };
    errors.value = {};
    editMode.value = true;
    modalOpen.value = true;
}

async function submit() {
    errors.value = {};

    try {
        if (editMode.value) {
            // await axios.put(route("grupos.update", form.value.id), form.value);
            await axios.post(route('grupos.update', form.value.id), {
                ...form.value,
                _method: 'PUT'
            });
            toastSuccess("Grupo actualizado correctamente");
        } else {
            await axios.post(route("grupos.store"), form.value);
            toastSuccess("Grupo agregado correctamente");
        }

        router.reload({ only: ["grupos"] });
        modalOpen.value = false;

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    }
}

async function destroyItem(g) {

    const ok = await confirmDanger(
        "Eliminar servicio",
        `¿Seguro que deseas eliminar el grupo #${g.id} (${g.grupo})?`,
    );
    if (!ok) return;

    // await axios.delete(route("grupos.destroy", g.id));
    await axios.post(route('grupos.destroy', g.id), {
        ...form.value,
        _method: 'DELETE'
    });
    window.dispatchEvent(new CustomEvent("toast", {
        detail: { type: "success", message: "Grupo eliminado" }
    }));

    router.reload({ only: ["grupos"] });

}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Listado de Gupos" />
        <template #title>Panel general de alumbrado</template>

        <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Grupos</h1>

        <!-- Botón crear -->
        <button
            @click="openCreate"
            class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
        >
            + Nuevo Grupo
        </button>

            <DataTableCustom
                title="Listado de Grupos"
                :items="props.grupos"
                :columns="[
                    { label: '🆔 ID', field: 'id', sortable: true },
                    { label: '🎟️ Grupo', field: 'grupo', sortable: true },
                    { label: '📅 Clave', field: 'clave', sortable: true }
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
                :title="editMode ? 'Editar Grupo' : 'Nuevo Grupo'"
                @submit="submit"
            >
                <template #default>

                    <transition name="fade-up">
                        <div key="modal-fields">

                            <!-- GRUPO -->
                            <div class="mb-5">
                                <label class="block mb-1 font-medium text-slate-200">Nombre del Grupo</label>

                                <div class="relative">
                                    <span class="emoji-icon">🗂️</span>
                                    <input
                                        v-model="form.grupo"
                                        class="input-field"
                                        placeholder="Ej. Residencial Norte, Comercial A"
                                    />
                                </div>

                                <p v-if="errors.grupo" class="text-sm text-rose-500">
                                    {{ errors.grupo[0] }}
                                </p>
                            </div>

                            <!-- CLAVE -->
                            <div class="mb-5">
                                <label class="block mb-1 font-medium text-slate-200">Clave del Grupo</label>

                                <div class="relative">
                                    <span class="emoji-icon">🔑</span>
                                    <input
                                        v-model="form.clave"
                                        class="input-field"
                                        placeholder="Ej. RN-01, C-A"
                                    />
                                </div>

                                <p v-if="errors.clave" class="text-sm text-rose-500">
                                    {{ errors.clave[0] }}
                                </p>
                            </div>

                        </div>
                    </transition>

                </template>
            </ModalForm>

    </div>
    </AuthenticatedLayout>
</template>

<!--<style scoped>-->

<!--.input-field {-->
<!--    @apply w-full pr-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50-->
<!--    text-slate-700 shadow-sm cursor-pointer transition-all duration-200;-->
<!--}-->

<!--/* EMOJI ICON INSIDE INPUT */-->
<!--.emoji-icon {-->
<!--    @apply absolute left-3 top-1/2 -translate-y-1/2 text-lg select-none opacity-80;-->
<!--}-->

<!--/* ✨ GLOW pastel en hover */-->
<!--.input-field:hover {-->
<!--    box-shadow: 0 0 0 4px rgba(125, 211, 252, 0.25); /* sky-300 glow */-->
<!--}-->

<!--/* ✨ GLOW pastel en focus */-->
<!--.input-field:focus {-->
<!--    box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.35); /* emerald-300 glow */-->
<!--    border-color: rgba(52, 211, 153, 0.7);-->
<!--}-->

<!--/* ANIMACIÓN DE ENTRADA */-->
<!--.fade-up-enter-from {-->
<!--    opacity: 0;-->
<!--    transform: translateY(10px) scale(0.98);-->
<!--}-->

<!--.fade-up-enter-active {-->
<!--    transition: all 300ms cubic-bezier(0.24, 0.8, 0.42, 1);-->
<!--}-->

<!--.fade-up-leave-to {-->
<!--    opacity: 0;-->
<!--    transform: translateY(-10px) scale(0.98);-->
<!--}-->

<!--.fade-up-leave-active {-->
<!--    transition: all 200ms cubic-bezier(0.24, 0.8, 0.42, 1);-->
<!--}-->

<!--/* Cursor manito en inputs */-->
<!--input, button, select {-->
<!--    cursor: pointer;-->
<!--}-->
<!--</style>-->

