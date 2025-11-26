<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: Boolean,   // Controla si el modal está abierto
    title: String,         // Título del modal
    size: {
        type: String,
        default: "md",     // sm, md, lg, xl
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
    primaryText: {
        type: String,
        default: "Guardar",
    },
    cancelText: {
        type: String,
        default: "Cancelar",
    },
});

const emit = defineEmits(["update:modelValue", "submit", "cancel"]);

// Tamaños del modal
const sizeClass = computed(() => {
    return {
        sm: "max-w-sm",
        md: "max-w-lg",
        lg: "max-w-2xl",
        xl: "max-w-4xl",
    }[props.size];
});
</script>

<template>
    <teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50"
        >
            <!-- Contenedor del modal -->
            <div
                class="w-full p-6 rounded-2xl shadow-2xl
                       bg-white dark:bg-slate-900
                       border border-white/10 animate-fadeIn"
                :class="sizeClass"
            >
                <!-- Título -->
                <h2 class="text-xl font-bold mb-4 text-slate-900 dark:text-slate-100">
                    {{ title }}
                </h2>

                <!-- Contenido personalizado -->
                <slot />

                <!-- Footer -->
                <div v-if="showFooter" class="flex justify-end gap-3 mt-6">
                    <button
                        @click="emit('cancel'); emit('update:modelValue', false)"
                        class="px-4 py-2 rounded-lg
                               bg-slate-200 dark:bg-slate-700
                               text-slate-900 dark:text-slate-100
                               hover:bg-slate-300 dark:hover:bg-slate-600"
                    >
                        {{ cancelText }}
                    </button>

                    <button
                        @click="emit('submit')"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                    >
                        {{ primaryText }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>
