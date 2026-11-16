import axios from 'axios';
import { useAuthStore } from '../store/authStore';

// In dev, Vite's proxy (see vite.config.ts) forwards "/api" to Laravel,
// so the browser only ever talks to its own origin — no CORS involved.
// In production, point this at your deployed Laravel API URL.
export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
  headers: {
    Accept: 'application/json',
  },
});

// Attach the Sanctum token to every outgoing request, if we have one.
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// If the API ever returns 401, the token is dead — clear it and
// let the app redirect to /login.
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
    }
    return Promise.reject(error);
  }
);
