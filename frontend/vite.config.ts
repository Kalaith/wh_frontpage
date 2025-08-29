import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(() => {
  // Fixed base path for frontpage deployment
  const base = '/';

  return {
    base,
    plugins: [
      react(),
      tailwindcss(),
    ],
    server: {
      proxy: {
        '/api': {
          target: 'http://127.0.0.1/frontpage',
          changeOrigin: true,
          secure: false,
        }
      }
    }
  };
});
