/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",
    "./widgets/**/*.php",
    "./views/layouts/**/*.php",
    "./web/js/**/*.js",
  ],
  safelist: [
    'chat',
    'chat-start',
    'chat-end',
    'chat-image',
    'chat-header',
    'chat-bubble',
    'chat-bubble-primary',
    'text-primary-content'
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require("daisyui"),
  ],
  daisyui: {
    themes: [
      {
        atsys_theme: {
          "color-scheme": "light",
          "primary": "#134C42",
          "primary-content": "#ffffff",
          "secondary": "#D926A9",
          "accent": "#1FB2A6",
          "neutral": "#2a323c",
          "base-100": "#ffffff",
          "base-200": "#F2F2F2",
          "base-300": "#E5E6E6",
          "info": "#3ABFF8",
          "success": "#36D399",
          "warning": "#FBBD23",
          "error": "#F87272",
        }
      },
      {
        atsys_dark: {
          "color-scheme": "dark",
          "primary": "#134C42",
          "primary-content": "#ffffff",
          "secondary": "#1a5e52",
          "accent": "#2dd4bf",
          "neutral": "#111827",
          "neutral-content": "#9ca3af",
          "base-100": "#0d1117",
          "base-200": "#161b22",
          "base-300": "#21262d",
          "base-content": "#ebedef",
          "info": "#3abff8",
          "success": "#36d399",
          "warning": "#fbbd23",
          "error": "#f87272",
        }
      },
      "light",
    ],
    base: true,
    styled: true,
    utils: true,
  },
}