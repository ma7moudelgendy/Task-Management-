import { apiDelete, apiGet, apiPatch, apiPost } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import {
  AddProjectMemberRequest,
  CreateProjectRequest,
  Project,
  ProjectAnalytics,
  ProjectMember,
  UpdateProjectRequest,
} from '../types/projects.types';
import { PaginatedData } from '@/types/common';
import { ProjectRole } from '@/types/enums';

export const projectsService = {
  async listProjects(params?: { team_id?: number; status?: string; page?: number; per_page?: number }): Promise<PaginatedData<Project>> {
    const query = new URLSearchParams();
    if (params?.team_id) query.append('team_id', String(params.team_id));
    if (params?.status) query.append('status', params.status);
    if (params?.page) query.append('page', String(params.page));
    if (params?.per_page) query.append('per_page', String(params.per_page));

    return apiGet<PaginatedData<Project>>(`${API_ENDPOINTS.PROJECTS.LIST}?${query.toString()}`);
  },

  async createProject(data: CreateProjectRequest): Promise<Project> {
    return apiPost<Project>(API_ENDPOINTS.PROJECTS.CREATE, data);
  },

  async getProject(id: number | string): Promise<Project> {
    return apiGet<Project>(API_ENDPOINTS.PROJECTS.SHOW(id));
  },

  async updateProject(id: number | string, data: UpdateProjectRequest): Promise<Project> {
    return apiPatch<Project>(API_ENDPOINTS.PROJECTS.UPDATE(id), data);
  },

  async deleteProject(id: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.PROJECTS.DELETE(id));
  },

  async listMembers(projectId: number | string): Promise<ProjectMember[]> {
    return apiGet<ProjectMember[]>(API_ENDPOINTS.PROJECTS.MEMBERS(projectId));
  },

  async addMember(projectId: number | string, data: AddProjectMemberRequest): Promise<ProjectMember> {
    return apiPost<ProjectMember>(API_ENDPOINTS.PROJECTS.MEMBERS(projectId), data);
  },

  async updateMemberRole(projectId: number | string, userId: number | string, role: ProjectRole): Promise<void> {
    return apiPatch<void>(API_ENDPOINTS.PROJECTS.MEMBER_ROLE(projectId, userId), { role });
  },

  async removeMember(projectId: number | string, userId: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.PROJECTS.REMOVE_MEMBER(projectId, userId));
  },

  async getAnalytics(projectId: number | string): Promise<ProjectAnalytics> {
    return apiGet<ProjectAnalytics>(API_ENDPOINTS.PROJECTS.ANALYTICS(projectId));
  },
};

