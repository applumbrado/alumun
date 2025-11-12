// resources/js/plugins/sweetalert.js

import Swal from 'sweetalert2';

export default {
    install: (app) => {
        // Hace que Swal esté disponible globalmente como $swal
        app.config.globalProperties.$swal = Swal;

        // Opcional: Proporciona una función simple para la sustitución de 'alert()'
        app.config.globalProperties.$alert = (message, title = 'Atención', icon = 'info') => {
            return Swal.fire({
                title: title,
                text: message,
                icon: icon,
                confirmButtonText: 'Aceptar'
            });
        };
    },
};
