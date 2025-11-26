// Utils.js

export const Utils = {
    formatDate(dateStr) {
        if (!dateStr) return ''
        const date = new Date(dateStr)

        // ✅ Corregir desfase horario
        const correctedDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000)

        return correctedDate.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        })
    },

    formatDateTime(dateStr) {
        if (!dateStr) return ''
        const date = new Date(dateStr)
        // const correctedDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000)
        const correctedDate = new Date(date.getTime() + date.getTimezoneOffset())

        return correctedDate.toLocaleString('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        })
    },

    formatCurrency(value) {
        // 1. Manejar valores nulos o indefinidos
        if (value === null || value === undefined) {
            return '';
        }

        // Convertir el valor a número y asegurar que es válido
        const numericValue = Number(value);

        if (isNaN(numericValue)) {
            return '';
        }

        if (numericValue === 0) {
            return '';
        }

        // 2. Determinar si el número es un entero (sin decimales)
        const isInteger = numericValue === Math.floor(numericValue);

        // 3. Configurar las opciones de formato
        const options = {
            style: 'decimal', // Usar 'decimal' para evitar el signo de pesos ($)
            minimumFractionDigits: isInteger ? 0 : 2, // Si es entero, 0 decimales. Si no, 2.
            maximumFractionDigits: isInteger ? 0 : 2, // Limitar siempre a 2 decimales cuando no es entero.
        };

        // 4. Aplicar el formato
        return new Intl.NumberFormat('es-MX', options).format(numericValue);
    },


    getDatePart(value) {
        if (!value) return null
        if (typeof value === 'string') {
            // si viene como '2025-11-05T10:23:00', nos quedamos con el día
            return value.split('T')[0]
        }
        const d = new Date(value)
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, '0')
        const day = String(d.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
    },



}
