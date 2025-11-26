<script setup>
import {
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    DocumentDuplicateIcon
} from "@heroicons/vue/24/outline";

// import "@fortawesome/fontawesome-free/css/all.min.css";

const props = defineProps({
    onEdit: Function,
    onDelete: Function,
    onView: Function,
    onDuplicate: Function,
    size: {
        type: String,
        default: "xlg1",
    },
});

const sizeClass = {
    sm: "w-4 h-4 p-1",
    md: "w-5 h-5 p-2",
    lg: "w-6 h-6 p-3",
    xlg1: "w-8 h-8 p-1 cursor-pointer",

};
</script>

<template>
    <div class="flex gap-2">

        <!-- View -->
        <button
            v-if="onView"
            @click="onView()"
            :class="[
                'rounded-full bg-slate-500/20 text-slate-300 hover:bg-slate-500/40',
                'backdrop-blur shadow border border-white/10 transition',
                sizeClass[size]
            ]"
            title="Ver"
        >
            <EyeIcon class="w-full h-full" />
<!--            <i class="fa-solid fa- text-xl"></i>-->
        </button>

        <!-- Edit -->
        <button
            v-if="onEdit"
            @click="onEdit()"
            :class="[
                'rounded-full bg-blue-500/20 text-blue-300 hover:bg-blue-500/40',
                'backdrop-blur shadow border border-white/10 transition',
                sizeClass[size]
            ]"
            title="Editar"
        >
            <PencilSquareIcon class="w-full h-full" />
<!--            <i class="fa-solid fa-pen-to-square text-xl"></i>-->
        </button>

        <!-- Duplicate -->
        <button
            v-if="onDuplicate"
            @click="onDuplicate()"
            :class="[
                'rounded-full bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/40',
                'backdrop-blur shadow border border-white/10 transition',
                sizeClass[size]
            ]"
            title="Duplicar"
        >
            <DocumentDuplicateIcon class="w-full h-full" />
<!--            <i class="fa-solid fa-sheet text-xl"></i>-->
        </button>

        <!-- Delete -->
        <button
            v-if="onDelete"
            @click="onDelete()"
            :class="[
                'rounded-full bg-red-500/20 text-red-300 hover:bg-red-500/40',
                'backdrop-blur shadow border border-white/10 transition',
                sizeClass[size]
            ]"
            title="Eliminar"
        >
            <TrashIcon class="w-full h-full" />
<!--            <i class="fa-solid fa-trash text-xl"></i>-->
        </button>

    </div>
</template>

<style scoped>
button {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
