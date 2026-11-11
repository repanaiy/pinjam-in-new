import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            // Palet: sidebar navy slate-800 (#1E293B), aksen emerald-700 (#047857) / emerald-500 (#10B981),
            // latar halaman slate-50 (#F8FAFC) — semuanya warna bawaan Tailwind.
            colors: {
                ink: '#222222',
            },
        },
    },
    plugins: [forms],
};
