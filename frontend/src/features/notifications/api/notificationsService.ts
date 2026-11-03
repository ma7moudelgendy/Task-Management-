import { apiGet, apiPost } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import { AppNotification } from '../types/notifications.types';
import { PaginatedData } from '@/types/common';

export const notificationsService = {
  async listNotifications(unreadOnly = false, page = 1, perPage = 20): Promise<PaginatedData<AppNotification>> {
    return apiGet<PaginatedData<AppNotification>>(
      `${API_ENDPOINTS.NOTIFICATIONS.LIST}?unread_only=${unreadOnly}&page=${page}&per_page=${perPage}`
    );
  },

  async markAsRead(id: string): Promise<void> {
    return apiPost<void>(API_ENDPOINTS.NOTIFICATIONS.MARK_READ(id));
  },
};

