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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // ADD YOUR CUSTOM BRAND COLORS HERE
            colors: {
                brand: {
                    navy: '#003761',  // The dark navy for active/hover states
                    blue: '#159cdb',  // The bright blue for accents/logo
                    yellow: '#fcb503', // The yellow for the logo
                }
            }
        },
    },

    plugins: [forms],
};