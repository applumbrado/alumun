import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                alumun: {
                    guinda: '#7b0015',   // rojo guinda
                    pino: '#0b3d2e',     // verde pino oscuro
                    mostaza: '#d4a017',  // amarillo dorado
                    gris: '#4b5563',     // gris medio
                    grisClaro: '#e5e7eb',
                    fondo: '#0b1520',    // fondo oscuro
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
