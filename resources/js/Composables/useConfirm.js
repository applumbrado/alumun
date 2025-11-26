import Swal from "sweetalert2";

export function confirmInfo(title = "¿Continuar?", text = "") {
    return Swal.fire({
        title,
        text,
        icon: "question",
        background: "#1f2937",
        color: "#f3f4f6",
        showCancelButton: true,
        confirmButtonText: "Aceptar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: true,
    }).then(r => r.isConfirmed);
}

export function confirmDanger(title = "¿Estás seguro?", text = "") {
    return Swal.fire({
        title,
        text,
        icon: "warning",
        background: "#1f2937",
        color: "#f3f4f6",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        reverseButtons: true,     // Cancelar a la derecha (más seguro)
        focusCancel: true,        // Cancelar como predeterminado
        buttonsStyling: true,
    }).then(r => r.isConfirmed);
}

export function confirmSuccess(title = "Todo correcto", text = "") {
    return Swal.fire({
        title,
        text,
        icon: "success",
        background: "#1f2937",
        color: "#f3f4f6",
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#059669",
        buttonsStyling: true,
    }).then(r => r.isConfirmed);
}
