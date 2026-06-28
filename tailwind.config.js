import containerQueries from '@tailwindcss/container-queries';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            // Ported from the previous inline CDN config so font utilities keep working.
            fontFamily: {
                // Poppins becomes the global default (Preflight applies `sans` to <html>).
                sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                poppins: ['Poppins', 'sans-serif'],
                sugo: ['Sugo Pro Display', 'sans-serif'],
            },
            // Realises the `animate-bounce-slight` class used on the splash screen
            // (previously a no-op because it was never defined).
            animation: {
                'bounce-slight': 'bounce-slight 1s ease-in-out infinite',
            },
            keyframes: {
                'bounce-slight': {
                    '0%, 100%': { transform: 'translateY(-6%)' },
                    '50%': { transform: 'translateY(0)' },
                },
            },
        },
    },
    plugins: [containerQueries],
};
