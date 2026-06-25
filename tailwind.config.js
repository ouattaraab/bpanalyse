/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.{js,jsx,ts,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                // Serif éditorial (autorité) + grotesque humaniste (corps).
                display: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
                sans: ['"Hanken Grotesk"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                // Papier chaud + encre marine (neutres teintés chaud).
                paper: '#F7F4EE',
                surface: '#FFFEFB',
                ink: {
                    DEFAULT: '#1C2530',
                    soft: '#45505F',
                    muted: '#857F73',
                },
                line: '#E7E0D4',
                // Accent de marque unique : viridian (confiance / vérifié).
                brand: {
                    50: '#E9F1EE',
                    100: '#CFE3DC',
                    500: '#0F6E62',
                    600: '#0B5A50',
                    700: '#08443D',
                },
                // Sémantique « à vérifier » uniquement.
                warn: {
                    50: '#F8EFD8',
                    100: '#F0E0B8',
                    600: '#9A6B12',
                    700: '#7A540C',
                },
                flag: '#A23B2C', // alerte forte (chiffre faux)
            },
            borderRadius: {
                xl: '0.875rem',
                '2xl': '1.25rem',
            },
            boxShadow: {
                card: '0 1px 2px rgba(28,37,48,0.04), 0 8px 24px -12px rgba(28,37,48,0.12)',
                lift: '0 12px 40px -16px rgba(28,37,48,0.28)',
            },
            keyframes: {
                'rise-in': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
            animation: {
                'rise-in': 'rise-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 0.4s ease-out both',
            },
        },
    },
    plugins: [],
};
