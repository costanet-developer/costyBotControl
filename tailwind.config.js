import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                'dark-bg': 'var(--color-bg)',
                'dark-panel': 'var(--color-panel)',
                'dark-card': 'var(--color-card)',
                'dark-border': 'var(--color-border)',
                'dark-text': 'var(--color-text)',
                'dark-muted': 'var(--color-muted)',
                'light-bg': '#F2F2F2',
                'light-panel': '#FFFFFF',
                'light-card': '#E9E9E9',
                'light-border': '#D4D4D4',
                'light-text': '#1E1E1E',
                'light-muted': '#5A5A5A',
                corp: '#E60012',
                'corp-dim': '#B3000E',
                info: '#0A66C2',
            },
        },
    },

    plugins: [forms],
};
