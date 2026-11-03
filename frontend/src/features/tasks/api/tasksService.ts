import { apiDelete, apiGet, apiPatch, apiPost, apiPut, apiClient } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import {
  ChangeStatusRequest,
  CreateTaskRequest,
  Task,
  TaskAttachment,
  TaskComment,
  TaskDependency,
  TaskStatusHistory,
  UpdateTaskRequest,
} from '../types/tasks.types';
import { PaginatedData } from '@/types/common';
import { TaskStatus } from '@/types/enums';

export const tasksService = {
  async listProjectTasks(
    projectId: number | string,
    params?: { status?: string; priority?: string; assignee_id?: number; sort_by?: string; sort_dir?: string; page?: number; per_page?: number }
  ): Promise<PaginatedData<Task>> {
    const query = new URLSearchParams();
    if (params?.status) query.append('status', params.status);
    if (params?.priority) query.append('priority', params.priority);
    if (params?.assignee_id) query.append('assignee_id', String(params.assignee_id));
    if (params?.sort_by) query.append('sort_by', params.sort_by);
    if (params?.sort_dir) query.append('sort_dir', params.sort_dir);
    if (params?.page) query.append('page', String(params.page));
    if (params?.per_page) query.append('per_page', String(params.per_page));

    return apiGet<PaginatedData<Task>>(`${API_ENDPOINTS.PROJECTS.TASKS(projectId)}?${query.toString()}`);
  },

  async createTask(projectId: number | string, data: CreateTaskRequest): Promise<Task> {
    return apiPost<Task>(API_ENDPOINTS.PROJECTS.TASKS(projectId), data);
  },

  async getTask(id: number | string): Promise<Task> {
    return apiGet<Task>(API_ENDPOINTS.TASKS.SHOW(id));
  },

  async updateTask(id: number | string, data: UpdateTaskRequest): Promise<Task> {
    return apiPatch<Task>(API_ENDPOINTS.TASKS.UPDATE(id), data);
  },

  async deleteTask(id: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.TASKS.DELETE(id));
  },

  async changeStatus(id: number | string, data: ChangeStatusRequest): Promise<Task> {
    return apiPost<Task>(API_ENDPOINTS.TASKS.STATUS(id), data);
  },

  async updateAssignees(id: number | string, assigneeIds: number[]): Promise<void> {
    return apiPut<void>(API_ENDPOINTS.TASKS.ASSIGNEES(id), { assignee_ids: assigneeIds });
  },

  async addDependency(taskId: number | string, dependsOnTaskId: number): Promise<void> {
    return apiPost<void>(API_ENDPOINTS.TASKS.DEPENDENCIES(taskId), { depends_on_task_id: dependsOnTaskId });
  },

  async removeDependency(taskId: number | string, dependencyId: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.TASKS.REMOVE_DEPENDENCY(taskId, dependencyId));
  },

  async getStatusHistory(taskId: number | string): Promise<TaskStatusHistory[]> {
    return apiGet<TaskStatusHistory[]>(API_ENDPOINTS.TASKS.HISTORY(taskId));
  },

  // Comments
  async listComments(taskId: number | string): Promise<PaginatedData<TaskComment>> {
    return apiGet<PaginatedData<TaskComment>>(API_ENDPOINTS.TASKS.COMMENTS(taskId));
  },

  async addComment(taskId: number | string, body: string): Promise<TaskComment> {
    return apiPost<TaskComment>(API_ENDPOINTS.TASKS.COMMENTS(taskId), { body });
  },

  async updateComment(commentId: number | string, body: string): Promise<TaskComment> {
    return apiPatch<TaskComment>(API_ENDPOINTS.COMMENTS.UPDATE(commentId), { body });
  },

  async deleteComment(commentId: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.COMMENTS.DELETE(commentId));
  },

  // Attachments
  async uploadAttachment(taskId: number | string, file: File): Promise<TaskAttachment> {
    const formData = new FormData();
    formData.append('file', file);
    return apiPost<TaskAttachment>(API_ENDPOINTS.TASKS.ATTACHMENTS(taskId), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  async deleteAttachment(taskId: number | string, attachmentId: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.ATTACHMENTS.DELETE(taskId, attachmentId));
  },

  async downloadAttachment(taskId: number | string, attachmentId: number | string, filename: string): Promise<void> {
    const res = await apiClient.get(API_ENDPOINTS.ATTACHMENTS.DOWNLOAD(taskId, attachmentId), {
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
};

