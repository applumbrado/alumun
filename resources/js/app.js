import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy'; // 👈 generado por Laravel (opcional)
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs'
import VueToast from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'
import sweetalertPlugin from './plugins/sweetalert';
import '@fortawesome/fontawesome-free/css/all.min.css';

import axios from 'axios';
axios.defaults.withCredentials = true;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const isLocal = window.location.hostname === 'localhost';
const echoHost = isLocal ? 'http://localhost:8000' : 'https://alumbrado.villahermosa.gob.mx';

// Ziggy.url = 'http://localhost:8000'
Ziggy.url = echoHost

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(LaravelPermissionToVueJS)
            .use(VueToast, {
                position: 'top-right',
                duration: 3000,
                dismissible: true,
            })
            .use(sweetalertPlugin)
            .mount(el);
    },
    progress: {
        color: '#043eea',
    },
});


// 🔔 SUSCRIPCIÓN GLOBAL AL CAMBIO DE PERIODO
// if (window.Echo) {
//     console.log('✔ window.Echo está inicializado')
//     window.Echo.channel('alumun.periodos')
//         .listen('.PeriodoVigenteChanged', (e) => {
//             console.log('📡 Evento PeriodoVigenteChanged recibido en frontend:', e)
//
//             router.reload({
//                 only: ['periodo_vigente'],
//                 preserveScroll: true,
//                 preserveState: true,
//             })
//         })
// } else {
//     console.log('❌ window.Echo no está inicializado')
// }


if (window.Echo) {
    console.log('✔ window.Echo está inicializado')

    const canal = window.Echo.channel('alumun.periodos')

    canal.on('PeriodoVigenteChanged', (e) => {
        console.log('🟢 Evento PeriodoVigenteChanged recibido en frontend (via .on):', e)

        router.reload({
            only: ['periodo_vigente'],
            preserveScroll: true,
            preserveState: true,
        })
    })
} else {
    console.log('❌ window.Echo no está inicializado')
}
