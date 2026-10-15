/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        night: {
          950: '#070a10',
          900: '#0b0f19',
          850: '#0e1422',
          800: '#131b2e',
          750: '#18223a',
          700: '#1e293b',
          600: '#334155',
          500: '#475569',
          400: '#64748b',
          300: '#94a3b8',
          200: '#cbd5e1',
          100: '#f1f5f9',
        },
        corporate: {
          950: '#0a0f1d',
          900: '#0f172a',
          850: '#141e33',
          800: '#1e293b',
          700: '#334155',
          600: '#475569',
        },
        brand: {
          DEFAULT: '#059669',
          dark: '#047857',
          light: '#10b981',
          subtle: '#ecfdf5',
        },
        slate: {
          850: '#141d2e',
          750: '#243044',
          650: '#3d4d65',
        }
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      minHeight: {
        'touch': '60px',
      },
      minWidth: {
        'touch': '60px',
      },
      boxShadow: {
        'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
      }
    },
  },
  plugins: [],
}
