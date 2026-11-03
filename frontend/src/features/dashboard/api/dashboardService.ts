import { apiGet } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import { DashboardData } from '../types/dashboard.types';

export const dashboardService = {
  async getDashboard(): Promise<DashboardData> {
    return apiGet<DashboardData>(API_ENDPOINTS.DASHBOARD);
  },
};

