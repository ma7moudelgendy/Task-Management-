import { apiGet, apiPatch, apiPost } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import {
  AuthResponseData,
  ForgotPasswordRequest,
  LoginRequest,
  RegisterRequest,
  ResetPasswordRequest,
  UpdateProfileRequest,
} from '../types/auth.types';
import { User } from '@/types/user';

export const authService = {
  async register(data: RegisterRequest): Promise<AuthResponseData> {
    return apiPost<AuthResponseData>(API_ENDPOINTS.AUTH.REGISTER, data);
  },

  async login(data: LoginRequest): Promise<AuthResponseData> {
    return apiPost<AuthResponseData>(API_ENDPOINTS.AUTH.LOGIN, {
      ...data,
      device_name: data.device_name || 'TaskFlow Web App',
    });
  },

  async logout(): Promise<void> {
    return apiPost<void>(API_ENDPOINTS.AUTH.LOGOUT);
  },

  async getMe(): Promise<{ user: User }> {
    return apiGet<{ user: User }>(API_ENDPOINTS.AUTH.ME);
  },

  async updateProfile(data: UpdateProfileRequest): Promise<{ user: User }> {
    return apiPatch<{ user: User }>(API_ENDPOINTS.AUTH.ME, data);
  },

  async forgotPassword(data: ForgotPasswordRequest): Promise<{ message: string }> {
    return apiPost<{ message: string }>(API_ENDPOINTS.AUTH.FORGOT_PASSWORD, data);
  },

  async resetPassword(data: ResetPasswordRequest): Promise<{ message: string }> {
    return apiPost<{ message: string }>(API_ENDPOINTS.AUTH.RESET_PASSWORD, data);
  },
};

