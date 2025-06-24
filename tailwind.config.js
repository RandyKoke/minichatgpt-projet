import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

   theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'pulse-dot': 'pulse-dot 1.5s ease-in-out infinite',
            },
            keyframes: {
                'pulse-dot': {
                    '0%, 20%': {
                        transform: 'scale(1)',
                        opacity: '1'
                    },
                    '50%': {
                        transform: 'scale(1.2)',
                        opacity: '0.7'
                    },
                    '100%': {
                        transform: 'scale(1)',
                        opacity: '1'
                    },
                },
            },
        },
    },

    plugins: [forms, typography],
};


