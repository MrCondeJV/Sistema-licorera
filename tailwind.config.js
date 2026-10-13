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
        neon: {
          green: '#00f59b',
          emerald: '#10b981',
          amber: '#f59e0b',
          cyan: '#06b6d4',
          rose: '#f43f5e',
          purple: '#8b5cf6',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      minHeight: {
        'touch': '60px',
      },
      minWidth: {
        'touch': '60px',
      }
    },
  },
  plugins: [],
}
