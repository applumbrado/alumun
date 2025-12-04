
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

const isLocal = window.location.hostname === 'localhost';
const echoHost = isLocal ? 'http://localhost:6001' : 'https://alumbrado.villahermosa.gob.mx:6001';


window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname === 'localhost'
        ? 'http://localhost:6001'
        : 'https://alumbrado.villahermosa.gob.mx:6001',
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



// Logs para debug
if (window.Echo.connector && window.Echo.connector.socket) {
    const socket = window.Echo.connector.socket

    socket.on('connect', () => {
        console.log('✅ Echo conectado a Socket.io')
    })

    socket.on('disconnect', (reason) => {
        console.log('❌ Echo desconectado de Socket.io. Razón:', reason)
    })

    socket.on('connect_error', (err) => {
        console.error('⚠️ Error de conexión Socket.io:', err)
    })
}

