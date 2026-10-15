import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Warna utama Tumbuh (hijau daun)
                leaf: {
                    50: '#f2faf5',
                    100: '#e3f3e8',
                    200: '#c3e6cf',
                    300: '#93d1aa',
                    400: '#5fb882',
                    500: '#3fa968',
                    600: '#2f8a53',
                    700: '#286e45',
                    800: '#1f3d2b',
                    900: '#162b1f',
                },
                // Air: progres minum
                water: {
                    100: '#eaf5fd',
                    300: '#8cc8f0',
                    500: '#2f8fd6',
                    700: '#1d5f91',
                },
                // XP & reward
                xp: {
                    100: '#fff6e8',
                    500: '#e79b2c',
                    700: '#a8650f',
                },
            },
        },
    },

    plugins: [forms],
};
