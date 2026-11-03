import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { teamsService } from '../api/teamsService';
import { Team, TeamMember } from '../types/teams.types';
import { TeamRole } from '@/types/enums';
import {
  Users,
  Plus,
  Shield,
  Trash2,
  UserPlus,
  X,
  ExternalLink,
} from 'lucide-react';
import { TaskFlowApiError } from '@/api/client';

export const TeamsView: React.FC = () => {
  const queryClient = useQueryClient();
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [selectedTeam, setSelectedTeam] = useState<Team | null>(null);
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [createError, setCreateError] = useState<string | null>(null);

  // Members modal state
  const [newUserId, setNewUserId] = useState('');
  const [newMemberRole, setNewMemberRole] = useState<TeamRole>('MEMBER');
  const [memberError, setMemberError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['teams'],
    queryFn: () => teamsService.listTeams(),
  });

  const { data: members, isLoading: membersLoading } = useQuery({
    queryKey: ['team-members', selectedTeam?.id],
    queryFn: () => (selectedTeam ? teamsService.listMembers(selectedTeam.id) : Promise.resolve([])),
    enabled: Boolean(selectedTeam),
  });

  const createTeamMutation = useMutation({
    mutationFn: (values: { name: string; slug?: string }) => teamsService.createTeam(values),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] });
      setCreateModalOpen(false);
      setName('');
      setSlug('');
      setCreateError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setCreateError(err.message);
      } else {
        setCreateError('Failed to create team.');
      }
    },
  });

  const deleteTeamMutation = useMutation({
    mutationFn: (teamId: number) => teamsService.deleteTeam(teamId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] });
    },
  });

  const addMemberMutation = useMutation({
    mutationFn: (values: { teamId: number; userId: number; role: TeamRole }) =>
      teamsService.addMember(values.teamId, { user_id: values.userId, role: values.role }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', selectedTeam?.id] });
      queryClient.invalidateQueries({ queryKey: ['teams'] });
      setNewUserId('');
      setMemberError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setMemberError(err.message);
      } else {
        setMemberError('Failed to add team member.');
      }
    },
  });

  const updateRoleMutation = useMutation({
    mutationFn: (values: { teamId: number; userId: number; role: TeamRole }) =>
      teamsService.updateMemberRole(values.teamId, values.userId, values.role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', selectedTeam?.id] });
    },
  });

  const removeMemberMutation = useMutation({
    mutationFn: (values: { teamId: number; userId: number }) =>
      teamsService.removeMember(values.teamId, values.userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', selectedTeam?.id] });
      queryClient.invalidateQueries({ queryKey: ['teams'] });
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Organizations & Teams</h1>
          <p className="text-sm text-slate-500 mt-1">
            Manage teams, department boundaries, and team members.
          </p>
        </div>
        <button
          onClick={() => setCreateModalOpen(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>Create New Team</span>
        </button>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-44 bg-slate-200 rounded-2xl" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {data?.data && data.data.length > 0 ? (
            data.data.map((team: Team) => (
              <div
                key={team.id}
                className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors"
              >
                <div>
                  <div className="flex items-start justify-between">
                    <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base">
                      {team.name.charAt(0).toUpperCase()}
                    </div>
                    {team.helpers.can_delete && (
                      <button
                        onClick={() => {
                          if (confirm(`Are you sure you want to delete team "${team.name}"?`)) {
                            deleteTeamMutation.mutate(team.id);
                          }
                        }}
                        className="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                        title="Delete Team"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  <h3 className="text-base font-bold text-slate-900 mt-4">{team.name}</h3>
                  <span className="text-xs text-slate-400 font-mono">slug: {team.slug}</span>
                  <div className="mt-3 flex items-center gap-2 text-xs text-slate-600">
                    <Users className="w-4 h-4 text-slate-400" />
                    <span>{team.member_count} Members</span>
                  </div>
                </div>

                <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                  <button
                    onClick={() => setSelectedTeam(team)}
                    className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1"
                  >
                    Manage Members
                    <ExternalLink className="w-3.5 h-3.5" />
                  </button>
                  <span className="text-[11px] text-slate-400">
                    {new Date(team.created_at).toLocaleDateString()}
                  </span>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-full p-12 bg-white rounded-2xl border border-slate-200 text-center">
              <Users className="w-10 h-10 text-slate-300 mx-auto mb-3" />
              <p className="text-slate-600 font-medium">No teams found.</p>
              <p className="text-xs text-slate-400 mt-1">Create your first team to collaborate with others.</p>
            </div>
          )}
        </div>
      )}

      {/* Create Team Modal */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 className="text-base font-bold text-slate-900">Create Team</h3>
              <button
                onClick={() => setCreateModalOpen(false)}
                className="text-slate-400 hover:text-slate-600 p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {createError && (
              <div className="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-xs">
                {createError}
              </div>
            )}

            <div className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Team Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="e.g. Engineering Core"
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Custom Slug <span className="text-slate-400 font-normal">(optional)</span>
                </label>
                <input
                  type="text"
                  value={slug}
                  onChange={(e) => setSlug(e.target.value)}
                  placeholder="e.g. engineering-core"
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                />
              </div>
            </div>

            <div className="mt-6 flex justify-end gap-2">
              <button
                onClick={() => setCreateModalOpen(false)}
                className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100"
              >
                Cancel
              </button>
              <button
                disabled={!name.trim() || createTeamMutation.isPending}
                onClick={() => createTeamMutation.mutate({ name, slug: slug.trim() || undefined })}
                className="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50"
              >
                {createTeamMutation.isPending ? 'Creating...' : 'Create Team'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Team Members Drawer/Modal */}
      {selectedTeam && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-xl w-full p-6 shadow-xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <h3 className="text-base font-bold text-slate-900">{selectedTeam.name} Members</h3>
                <p className="text-xs text-slate-500">Manage memberships and role permissions</p>
              </div>
              <button
                onClick={() => setSelectedTeam(null)}
                className="text-slate-400 hover:text-slate-600 p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Add Member Form */}
            {selectedTeam.helpers.can_manage_members && (
              <div className="my-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <h4 className="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1.5">
                  <UserPlus className="w-3.5 h-3.5 text-blue-600" />
                  Add Team Member
                </h4>
                {memberError && (
                  <div className="mb-2 p-2 rounded bg-red-50 text-red-600 text-xs">
                    {memberError}
                  </div>
                )}
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    value={newUserId}
                    onChange={(e) => setNewUserId(e.target.value)}
                    placeholder="User ID (e.g. 2)"
                    className="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"
                  />
                  <select
                    value={newMemberRole}
                    onChange={(e) => setNewMemberRole(e.target.value as TeamRole)}
                    className="px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"
                  >
                    <option value="MEMBER">MEMBER</option>
                    <option value="MANAGER">MANAGER</option>
                    <option value="OWNER">OWNER</option>
                  </select>
                  <button
                    disabled={!newUserId || addMemberMutation.isPending}
                    onClick={() =>
                      addMemberMutation.mutate({
                        teamId: selectedTeam.id,
                        userId: Number(newUserId),
                        role: newMemberRole,
                      })
                    }
                    className="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50"
                  >
                    Add
                  </button>
                </div>
              </div>
            )}

            {/* Members List */}
            <div className="flex-1 overflow-y-auto divide-y divide-slate-100">
              {membersLoading ? (
                <div className="p-8 text-center text-xs text-slate-400">Loading members...</div>
              ) : members && members.length > 0 ? (
                members.map((m: TeamMember) => (
                  <div key={m.id} className="py-3 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center">
                        {(m.name || m.user?.name)?.charAt(0).toUpperCase() || 'U'}
                      </div>
                      <div>
                        <p className="text-xs font-bold text-slate-900">{m.name || m.user?.name || `User #${m.user_id}`}</p>
                        <p className="text-[11px] text-slate-400">{m.email || m.user?.email}</p>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      {selectedTeam.helpers.can_change_role ? (
                        <select
                          value={m.role}
                          onChange={(e) =>
                            updateRoleMutation.mutate({
                              teamId: selectedTeam.id,
                              userId: m.user_id,
                              role: e.target.value as TeamRole,
                            })
                          }
                          className="text-xs font-semibold px-2 py-1 rounded-md border border-slate-200 bg-white"
                        >
                          <option value="OWNER">OWNER</option>
                          <option value="MANAGER">MANAGER</option>
                          <option value="MEMBER">MEMBER</option>
                        </select>
                      ) : (
                        <span className="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                          {m.role}
                        </span>
                      )}

                      {selectedTeam.helpers.can_manage_members && (
                        <button
                          onClick={() => {
                            if (confirm('Remove this user from the team?')) {
                              removeMemberMutation.mutate({
                                teamId: selectedTeam.id,
                                userId: m.user_id,
                              });
                            }
                          }}
                          className="p-1 text-slate-400 hover:text-red-600 rounded"
                          title="Remove Member"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                  </div>
                ))
              ) : (
                <p className="p-4 text-center text-xs text-slate-400">No members found.</p>
              )}
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end">
              <button
                onClick={() => setSelectedTeam(null)}
                className="px-4 py-2 rounded-lg bg-slate-100 text-xs font-semibold text-slate-700 hover:bg-slate-200"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

