import { TeamRole } from '@/types/enums';
import { TeamHelpers } from '@/types/common';
import { User } from '@/types/user';

export interface Team {
  id: number;
  name: string;
  slug: string;
  created_by: number;
  member_count: number;
  created_at: string;
  updated_at: string;
  helpers: TeamHelpers;
}

export interface TeamMember {
  id: number;
  team_id?: number;
  user_id: number;
  name: string;
  email: string;
  role: TeamRole;
  joined_at?: string;
  user?: User;
}

export interface CreateTeamRequest {
  name: string;
  slug?: string;
}

export interface UpdateTeamRequest {
  name: string;
  slug?: string;
}

export interface AddTeamMemberRequest {
  user_id: number;
  role: TeamRole;
}

