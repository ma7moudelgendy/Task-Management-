import { ProjectRole, ProjectStatus } from '@/types/enums';
import { ProjectHelpers } from '@/types/common';
import { User } from '@/types/user';

export interface Project {
  id: number;
  team_id: number;
  name: string;
  description?: string | null;
  status: ProjectStatus;
  start_date?: string | null;
  due_date?: string | null;
  created_by: number;
  member_count: number;
  tasks_count: number;
  created_at: string;
  updated_at: string;
  helpers: ProjectHelpers;
}

export interface ProjectMember {
  id: number;
  project_id?: number;
  user_id: number;
  name: string;
  email: string;
  role: ProjectRole;
  joined_at?: string;
  user?: User;
}

export interface CreateProjectRequest {
  team_id: number;
  name: string;
  description?: string;
  status?: ProjectStatus;
  start_date?: string;
  due_date?: string;
}

export interface UpdateProjectRequest {
  name?: string;
  description?: string;
  status?: ProjectStatus;
  start_date?: string;
  due_date?: string;
}

export interface AddProjectMemberRequest {
  user_id: number;
  role: ProjectRole;
}

export interface ProjectAnalytics {
  project_id: number;
  project_name: string;
  total_tasks: number;
  completed_tasks: number;
  completion_percentage: number;
  status_distribution: Record<string, number>;
  priority_distribution: Record<string, number>;
  assignee_workload: Array<{
    user_id: number;
    user_name: string;
    total_assigned: number;
    completed: number;
    in_progress: number;
  }>;
  overdue_count: number;
}

