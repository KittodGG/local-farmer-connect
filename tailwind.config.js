/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.html', './**/*.php', './src/**/*.js'],
  theme: {
    extend: {
      colors: {
        primary: "#52A447",
        background: "#F6FBF5",
        accent: "#8B8D98",
        'interactive': {
          1: "#EAF6E7",
          2: "#DCF0D8",
          3: "#CBE8C6",
        },
        'border-separator': {
          1: "#B6DDB0",
          2: "#9ACE92",
          3: "#71B867",
        },
        'accesibel-text-color': {
          1: "#377D2E",
          2: "#243C20",
          3: "#62636C",
          4: "#1E1F24",
        },          
      },
      fontFamily: {
        sans: ["Poppins", "sans-serif"],
      },
    },
  },
  plugins: [require("daisyui")],
};

