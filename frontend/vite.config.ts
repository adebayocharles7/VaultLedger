import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    // Optional: proxy avoids CORS entirely in dev by making requests
    // look same-origin from the browser's point of view.
    // If you use this, set VITE_API_BASE_URL="/api" in .env instead
    // of the full http://localhost:8000 URL.
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
});
