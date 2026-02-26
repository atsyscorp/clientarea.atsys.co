/** @type {import('tailwindcss').Config} */
module.exports = {
  // AQUÍ es donde Tailwind busca qué clases usaste.
  // Si estas rutas no coinciden, tu CSS saldrá vacío.
  content: [
    "./views/**/*.php",
    "./widgets/**/*.php",
    "./layouts/**/*.php",
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
    require("daisyui"), // Activamos el plugin
  ],
  // Configuración opcional de DaisyUI
  daisyui: {
    themes: [
      {
        atsys_theme: { // Nombre de tu tema personalizado

          // TU COLOR PRINCIPAL (El que quieres cambiar)
          "primary": "#134C42",  // <--- Pon aquí tu código HEX (ej: Azul índigo)
          "primary-content": "#ffffff", // Color del texto sobre el botón primario (Blanco)

          "secondary": "#D926A9", // Color secundario (Opcional)
          "accent": "#1FB2A6",    // Color de acento (Opcional)

          "neutral": "#2a323c",   // Color oscuro para textos/fondos neutros
          "base-100": "#ffffff",  // Color de fondo de la página (Blanco)
          "base-200": "#F2F2F2",  // Color de fondo secundario (Gris muy claro)
          "base-300": "#E5E6E6",  // Color de bordes

          "info": "#3ABFF8",
          "success": "#36D399",
          "warning": "#FBBD23",
          "error": "#F87272",
        },
      },
      "light", // Tema de respaldo
    ],
    base: true,        // Aplica estilos base al <body>
    styled: true,      // Aplica estilos a componentes
    utils: true,       // Agrega clases de utilidad responsive
  },
}