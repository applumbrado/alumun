import Echo from "laravel-echo";
import io from "socket.io-client";

import axios from 'axios';
// Detecta entorno automáticamente
const baseURL =
    import.meta.env.MODE === 'development'
        ? 'http://localhost:8000' // Laravel local
        : window.location.origin   // Producción (mismo dominio)

axios.defaults.withCredentials = true
axios.defaults.baseURL = baseURL
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';


// Siempre identifica las peticiones AJAX
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Busca el token CSRF que Laravel inyecta en tu layout
const tokenMeta = document.querySelector('meta[name="csrf-token"]');

if (tokenMeta) {
    const token = tokenMeta.getAttribute('content');

    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    } else {
        console.warn('⚠️ El meta[name="csrf-token"] no tiene contenido.');
    }
} else {
    console.warn('⚠️ No se encontró el meta[name="csrf-token"] en el documento.');
}

window.axios = axios;
window.io = io;

import $ from 'jquery';
window.$ = $;
window.jQuery = $;

// import 'jquery';
import 'select2/dist/js/select2.min.js';
import 'select2/dist/css/select2.min.css';

console.log(window.location.hostname);
const isLocal = window.location.hostname === 'localhost';
const echoHost = isLocal ? 'http://localhost:6001' : 'https://arjipagos.moriah.mx:6001';

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname === 'localhost'
        ? 'http://localhost:6001'
        : 'https://arjipagos.moriah.mx:6001',
    transports: ['polling', 'websocket'],  // usa ambos, polling primero
    forceNew: true,
    reconnectionAttempts: 10,
    reconnectionDelay: 20000,
    timeout: 100000,
    secure: true,
    forceTLS: true,
    rejectUnauthorized: false,
    auth: {
        headers: {
            Authorization: 'Bearer ' + localStorage.getItem('token') // si usas tokens
        }
    },

});

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
