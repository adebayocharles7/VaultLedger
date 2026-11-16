import { apiClient } from './client';
import type { LoginPayload, LoginResponse, User } from './types';

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export async function register(payload: RegisterPayload): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/register', payload);
  return data;
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/auth/login', payload);
  return data;
}

export async function fetchCurrentUser(): Promise<User> {
  const { data } = await apiClient.get<User>('/auth/me');
  return data;
}

export async function logoutRequest(): Promise<void> {
  await apiClient.post('/auth/logout');
}
