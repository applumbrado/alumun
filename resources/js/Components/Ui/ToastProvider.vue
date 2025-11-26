<script setup>
import { onMounted, onUnmounted } from "vue";
import { useToast } from "vue-toast-notification";

const toast = useToast();

// Handler global
function handleToastEvent(e) {
    const { type = "info", message = "" } = e.detail || {};

    const className =
        type === "success" ? "toast-alumun toast-success" :
            type === "error"   ? "toast-alumun toast-error" :
                type === "warning" ? "toast-alumun toast-warning" :
                    "toast-alumun toast-info";

    toast[type](message, {
        position: "top-right",
        duration: 3500,
        dismissible: true,
        pauseOnHover: true,
        className,
    });
}

onMounted(() => {
    window.addEventListener("toast", handleToastEvent);
});

onUnmounted(() => {
    window.removeEventListener("toast", handleToastEvent);
});
</script>

<template>
    <!-- No renderiza nada, solo escucha eventos -->
</template>
