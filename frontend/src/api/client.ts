import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { TaskFlowEnvelope } from '@/types/common';

export class TaskFlowApiError extends Error {
  public key: string;
  public code: number;
  public validationErrors: Record<string, string[]>;

  constructor(key: string, code: number, message: string, validationErrors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'TaskFlowApiError';
    this.key = key;
    this.code = code;
    this.validationErrors = validationErrors;
  }
}

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 15000,
});

// Request interceptor: attach Sanctum token
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor: parse TaskFlow envelope
apiClient.interceptors.response.use(
  (response: AxiosResponse): any => {
    // Binary/Blob responses pass straight through
    if (response.config.responseType === 'blob') {
      return response;
    }

    const envelope = response.data as TaskFlowEnvelope;

    // Happy path: transport is 200 and key is 'success'
    if (envelope?.key === 'success') {
      return envelope.data;
    }

    // Token missing, expired, or invalid
    if (envelope?.key === 'unauthenticated') {
      useAuthStore.getState().clearAuth();
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login?expired=1';
      }
      return Promise.reject(new TaskFlowApiError('unauthenticated', 401, envelope.msg || 'Session expired.'));
    }

    // Handled business errors or validation failures
    const validationErrors = envelope?.response_status?.validation_errors || {};
    return Promise.reject(
      new TaskFlowApiError(
        envelope?.key || 'fail',
        envelope?.code || 400,
        envelope?.msg || 'Request failed.',
        validationErrors
      )
    );
  },
  (error: AxiosError<TaskFlowEnvelope>) => {
    // If backend sent JSON with error status code (e.g. rate limit 429)
    if (error.response?.data && typeof error.response.data === 'object' && 'key' in error.response.data) {
      const envelope = error.response.data as TaskFlowEnvelope;
      return Promise.reject(
        new TaskFlowApiError(
          envelope.key,
          envelope.code || error.response.status,
          envelope.msg || error.message,
          envelope.response_status?.validation_errors || {}
        )
      );
    }

    return Promise.reject(
      new TaskFlowApiError(
        'exception',
        error.response?.status || 500,
        error.message || 'An unexpected network error occurred.'
      )
    );
  }
);

// Typed request helpers
export async function apiGet<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  const res = await apiClient.get(url, config);
  return res as unknown as T;
}

export async function apiPost<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await apiClient.post(url, data, config);
  return res as unknown as T;
}

export async function apiPatch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await apiClient.patch(url, data, config);
  return res as unknown as T;
}

export async function apiPut<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await apiClient.put(url, data, config);
  return res as unknown as T;
}

export async function apiDelete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  const res = await apiClient.delete(url, config);
  return res as unknown as T;
}
