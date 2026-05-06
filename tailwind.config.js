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
            // CUSTOM BRAND COLORS 
            colors: {
                brand: {
                    navy: {
                        DEFAULT: '#003761',
                        light: '#0b426e',   // used for borders, dark hover states
                    },
                    blue: {
                        DEFAULT: '#159cdb',
                        dark: '#1369a8',    // main header / table blue
                        darker: '#1d62c7',  // primary button blue
                        hover: '#0f588d',   // deeper hover
                    },
                    yellow: '#fcb503',
                }
            }
        },
    },

    plugins: [forms],
};