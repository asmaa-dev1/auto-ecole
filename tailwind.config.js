/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./pages/**/*.php",
    "./includes/**/*.php",
    "./components/**/*.php",
    "./assets/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1E40AF',
        secondary: '#1E293B',
        accent: '#3B82F6',
        success: '#059669',
        warning: '#D97706',
        danger: '#DC2626'
      },
      fontFamily: {
        sans: ['Poppins', 'sans-serif']
      }
    },
  },
  plugins: [],
}