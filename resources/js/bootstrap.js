
import _ from 'lodash'
window._ = _

import axios from 'axios'
window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Si usas meta csrf-token en el <head>
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}

// 🔌 Echo + Socket.io (v2.4.0)
import Echo from 'laravel-echo'
import io from 'socket.io-client'

window.io = io

// window.Echo = new Echo({
//     broadcaster: 'socket.io',
//     host: window.location.hostname + ':6001', // http://localhost:6001
//     transports: ['polling','websocket'],
// })

const echoPort = import.meta.env.VITE_ECHO_SERVER_PORT || 6001

const isLocal = window.location.hostname === 'localhost';
const echoHost = isLocal ? 'http://localhost:${echoPort}' : 'https://alumbrado.villahermosa.gob.mx:${echoPort}';

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: isLocal
        ? `http://localhost:${echoPort}`
        : `https://alumbrado.villahermosa.gob.mx:${echoPort}`,
    path: '/socket.io',
    transports: ['polling','websocket'],  // usa ambos, polling primero
});



// 🔥 Suscripción directa al evento crudo de Socket.io
if (window.Echo && window.Echo.connector && window.Echo.connector.socket) {
    const rawSocket = window.Echo.connector.socket;

    // DEBUG: ver TODO lo que entra por el socket
    const originalOnevent = rawSocket.onevent;
    rawSocket.onevent = function (packet) {
        console.log('🔥 RAW SOCKET EVENT:', packet);
        originalOnevent.call(this, packet);
    };

    // Escuchar el evento específico que esperamos
    rawSocket.on('alumun.periodos:PeriodoVigenteChanged', (payload) => {
        console.log('🟢 [raw] Evento alumun.periodos:PeriodoVigenteChanged recibido:', payload);

        // Lanzamos un evento de ventana para que lo escuche app.js
        window.dispatchEvent(new CustomEvent('PeriodoVigenteChanged', { detail: payload }));
    });

    const socket = window.Echo.connector.socket

    socket.on('connect', () => {
        console.log('✅ Echo conectado a Socket.io')
    })

    socket.on('disconnect', (reason) => {
        console.log('❌ Echo desconectado de Socket.io. Razón:', reason)
    })

    // socket.on('connect_error', (err) => {
    //     console.error('⚠️ Error de conexión Socket.io:', err)
    // })
    //
    socket.on('error', (err) => {
        console.error('🔥 Error Socket.io:', err)
    })


} else {
    console.log('⚠️ No se pudo acceder a window.Echo.connector.socket');
}
