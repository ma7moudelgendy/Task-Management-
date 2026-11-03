export interface NotificationData {
  task_id?: number;
  title?: string;
  project_id?: number;
  project_name?: string;
  assigner_id?: number;
  assigner_name?: string;
  message?: string;
}

export interface AppNotification {
  id: string;
  type: string;
  data: NotificationData;
  read_at: string | null;
  created_at: string;
}

