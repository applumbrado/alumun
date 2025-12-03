
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

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001', // http://localhost:6001
    transports: ['websocket', 'polling'],
})

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

