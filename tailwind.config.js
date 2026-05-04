/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#fade01',
                    hover: '#e5c800',
                    light: '#fff5b0',
                },
                dark: {
                    DEFAULT: '#0a0a0a',
                    surface: '#121212',
                    border: '#2a2a2a',
                    muted: '#888888',
                }
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
            },
            fontSize: {
                'xxs': '0.65rem',
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
