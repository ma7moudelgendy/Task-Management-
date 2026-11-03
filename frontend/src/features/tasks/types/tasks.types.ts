import { TaskPriority, TaskStatus } from '@/types/enums';
import { CommentHelpers, TaskHelpers } from '@/types/common';
import { User } from '@/types/user';

export interface Task {
  id: number;
  project_id: number;
  parent_task_id?: number | null;
  created_by: number;
  title: string;
  description?: string | null;
  status: TaskStatus;
  priority: TaskPriority;
  start_at?: string | null;
  due_at?: string | null;
  completed_at?: string | null;
  position: number;
  created_at: string;
  updated_at: string;
  assignees?: User[];
  subtasks?: Task[];
  helpers: TaskHelpers;
}

export interface TaskDependency {
  id: number;
  task_id: number;
  depends_on_task_id: number;
  depends_on_task?: Task;
}

export interface TaskStatusHistory {
  id: number;
  task_id: number;
  changed_by: number;
  old_status: TaskStatus;
  new_status: TaskStatus;
  reason?: string | null;
  created_at: string;
  user?: User;
}

export interface TaskComment {
  id: number;
  task_id: number;
  user_id: number;
  body: string;
  created_at: string;
  updated_at: string;
  user: User;
  helpers: CommentHelpers;
}

export interface TaskAttachment {
  id: number;
  task_id: number;
  uploaded_by: number;
  original_name: string;
  mime_type: string;
  size: number;
  created_at: string;
  user?: User;
}

export interface CreateTaskRequest {
  title: string;
  description?: string;
  priority?: TaskPriority;
  parent_task_id?: number | null;
  start_at?: string | null;
  due_at?: string | null;
  position?: number;
  assignee_ids?: number[];
}

export interface UpdateTaskRequest {
  title?: string;
  description?: string;
  priority?: TaskPriority;
  start_at?: string | null;
  due_at?: string | null;
  position?: number;
}

export interface ChangeStatusRequest {
  status: TaskStatus;
  reason?: string;
}

