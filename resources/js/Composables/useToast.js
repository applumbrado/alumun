import { useToast } from "vue-toast-notification";

// Tema personalizado Alumun
const toastOptions = {
    position: "top-right",
    duration: 3500,
    dismissible: true,
    pauseOnHover: true,
};

export function toastSuccess(message = "Operación exitosa") {
    const toast = useToast();
    toast.success(message, {
        ...toastOptions,
        className: "toast-alumun toast-success",
    });
}

export function toastError(message = "Ocurrió un error") {
    const toast = useToast();
    toast.error(message, {
        ...toastOptions,
        className: "toast-alumun toast-error",
    });
}

export function toastInfo(message = "Información") {
    const toast = useToast();
    toast.info(message, {
        ...toastOptions,
        className: "toast-alumun toast-info",
    });
}
