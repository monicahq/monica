/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        'gray-50': '#f7f7f7',
        'gray-100': '#e1e1e1',
        'gray-200': '#c9c9c9',
        'gray-300': '#b1b1b1',
        'gray-400': '#9a9a9a',
        'gray-500': '#828282',
        'gray-600': '#6a6a6a',
        'gray-700': '#525252',
        'gray-800': '#3a3a3a',
        'gray-900': '#222222',
        'gray-950': '#0a0a0a',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
