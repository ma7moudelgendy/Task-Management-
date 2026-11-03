import { TaskPriority, TaskStatus } from '@/types/enums';

export interface DashboardStats {
  status: Record<TaskStatus, number>;
  total_assigned: number;
  overdue_count: number;
}

export interface DashboardTaskItem {
  id: number;
  title: string;
  priority: TaskPriority;
  status: TaskStatus;
  due_at: string | null;
  project: {
    id: number;
    name: string;
  };
}

export interface ActivityItem {
  id: number;
  action: string;
  actor: {
    id: number;
    name: string;
    email: string;
  };
  project: {
    id: number;
    name: string;
  };
  metadata?: Record<string, unknown>;
  created_at: string;
}

export interface DashboardData {
  stats: DashboardStats;
  overdue_tasks: DashboardTaskItem[];
  upcoming_tasks: DashboardTaskItem[];
  recent_activity: ActivityItem[];
}

