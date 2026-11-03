import { apiDelete, apiGet, apiPatch, apiPost } from '@/api/client';
import { API_ENDPOINTS } from '@/api/endpoints';
import {
  AddTeamMemberRequest,
  CreateTeamRequest,
  Team,
  TeamMember,
  UpdateTeamRequest,
} from '../types/teams.types';
import { PaginatedData } from '@/types/common';
import { TeamRole } from '@/types/enums';

export const teamsService = {
  async listTeams(page = 1, perPage = 15): Promise<PaginatedData<Team>> {
    return apiGet<PaginatedData<Team>>(`${API_ENDPOINTS.TEAMS.LIST}?page=${page}&per_page=${perPage}`);
  },

  async createTeam(data: CreateTeamRequest): Promise<Team> {
    return apiPost<Team>(API_ENDPOINTS.TEAMS.CREATE, data);
  },

  async getTeam(id: number | string): Promise<Team> {
    return apiGet<Team>(API_ENDPOINTS.TEAMS.SHOW(id));
  },

  async updateTeam(id: number | string, data: UpdateTeamRequest): Promise<Team> {
    return apiPatch<Team>(API_ENDPOINTS.TEAMS.UPDATE(id), data);
  },

  async deleteTeam(id: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.TEAMS.DELETE(id));
  },

  async listMembers(teamId: number | string): Promise<TeamMember[]> {
    return apiGet<TeamMember[]>(API_ENDPOINTS.TEAMS.MEMBERS(teamId));
  },

  async addMember(teamId: number | string, data: AddTeamMemberRequest): Promise<TeamMember> {
    return apiPost<TeamMember>(API_ENDPOINTS.TEAMS.MEMBERS(teamId), data);
  },

  async updateMemberRole(teamId: number | string, userId: number | string, role: TeamRole): Promise<void> {
    return apiPatch<void>(API_ENDPOINTS.TEAMS.MEMBER_ROLE(teamId, userId), { role });
  },

  async removeMember(teamId: number | string, userId: number | string): Promise<void> {
    return apiDelete<void>(API_ENDPOINTS.TEAMS.REMOVE_MEMBER(teamId, userId));
  },
};

