import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { projectsService } from '../api/projectsService';
import { teamsService } from '@/features/teams/api/teamsService';
import { Project } from '../types/projects.types';
import { ProjectStatus } from '@/types/enums';
import {
  FolderKanban,
  Plus,
  Users,
  Calendar,
  CheckCircle,
  Clock,
  Trash2,
  X,
  ExternalLink,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { TaskFlowApiError } from '@/api/client';

const statusBadgeColors: Record<ProjectStatus, string> = {
  ACTIVE: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  ON_HOLD: 'bg-amber-50 text-amber-700 border-amber-200',
  COMPLETED: 'bg-blue-50 text-blue-700 border-blue-200',
  ARCHIVED: 'bg-slate-100 text-slate-600 border-slate-200',
};

export const ProjectsView: React.FC = () => {
  const queryClient = useQueryClient();
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [createModalOpen, setCreateModalOpen] = useState(false);

  // Form state
  const [teamId, setTeamId] = useState<number | ''>('');
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [startDate, setStartDate] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [createError, setCreateError] = useState<string | null>(null);

  const { data: teamsData } = useQuery({
    queryKey: ['teams'],
    queryFn: () => teamsService.listTeams(),
  });

  const { data: projectsData, isLoading } = useQuery({
    queryKey: ['projects', selectedStatus],
    queryFn: () => projectsService.listProjects({ status: selectedStatus || undefined }),
  });

  const createProjectMutation = useMutation({
    mutationFn: (values: {
      team_id: number;
      name: string;
      description?: string;
      start_date?: string;
      due_date?: string;
    }) => projectsService.createProject(values),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      setCreateModalOpen(false);
      setName('');
      setDescription('');
      setStartDate('');
      setDueDate('');
      setCreateError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setCreateError(err.message);
      } else {
        setCreateError('Failed to create project.');
      }
    },
  });

  const deleteProjectMutation = useMutation({
    mutationFn: (id: number) => projectsService.deleteProject(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
    },
  });

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Project Workspaces</h1>
          <p className="text-sm text-slate-500 mt-1">
            Organize roadmaps, sprints, and team deliverables.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <select
            value={selectedStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
            className="px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">All Statuses</option>
            <option value="ACTIVE">Active</option>
            <option value="ON_HOLD">On Hold</option>
            <option value="COMPLETED">Completed</option>
            <option value="ARCHIVED">Archived</option>
          </select>

          <button
            onClick={() => {
              if (teamsData?.data?.[0]?.id) {
                setTeamId(teamsData.data[0].id);
              }
              setCreateModalOpen(true);
            }}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-xs"
          >
            <Plus className="w-4 h-4" />
            <span>New Project</span>
          </button>
        </div>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-52 bg-slate-200 rounded-2xl" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {projectsData?.data && projectsData.data.length > 0 ? (
            projectsData.data.map((project: Project) => (
              <div
                key={project.id}
                className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-colors"
              >
                <div>
                  <div className="flex items-start justify-between">
                    <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full border ${statusBadgeColors[project.status]}`}>
                      {project.status}
                    </span>
                    {project.helpers.can_delete && (
                      <button
                        onClick={() => {
                          if (confirm(`Are you sure you want to delete "${project.name}"?`)) {
                            deleteProjectMutation.mutate(project.id);
                          }
                        }}
                        className="p-1 text-slate-400 hover:text-red-600 rounded"
                        title="Delete Project"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>

                  <h3 className="text-base font-bold text-slate-900 mt-3">{project.name}</h3>
                  <p className="text-xs text-slate-500 mt-1 line-clamp-2">
                    {project.description || 'No description provided.'}
                  </p>

                  <div className="mt-4 flex items-center gap-4 text-xs text-slate-500">
                    <div className="flex items-center gap-1.5">
                      <CheckCircle className="w-4 h-4 text-slate-400" />
                      <span>{project.tasks_count} Tasks</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Users className="w-4 h-4 text-slate-400" />
                      <span>{project.member_count} Members</span>
                    </div>
                  </div>

                  {(project.start_date || project.due_date) && (
                    <div className="mt-3 flex items-center gap-1.5 text-[11px] text-slate-400">
                      <Calendar className="w-3.5 h-3.5" />
                      <span>
                        {project.start_date || 'Start'} → {project.due_date || 'No deadline'}
                      </span>
                    </div>
                  )}
                </div>

                <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                  <Link
                    to={`/projects/${project.id}/board`}
                    className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1"
                  >
                    Open Kanban Board
                    <ExternalLink className="w-3.5 h-3.5" />
                  </Link>
                  <Link
                    to={`/projects/${project.id}/analytics`}
                    className="text-xs font-semibold text-slate-500 hover:text-slate-700"
                  >
                    Analytics
                  </Link>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-full p-12 bg-white rounded-2xl border border-slate-200 text-center">
              <FolderKanban className="w-10 h-10 text-slate-300 mx-auto mb-3" />
              <p className="text-slate-600 font-medium">No projects found.</p>
              <p className="text-xs text-slate-400 mt-1">Create a new project within a team to begin.</p>
            </div>
          )}
        </div>
      )}

      {/* Create Project Modal */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 className="text-base font-bold text-slate-900">Create Project</h3>
              <button onClick={() => setCreateModalOpen(false)} className="text-slate-400 hover:text-slate-600 p-1">
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
                <label className="block text-xs font-semibold text-slate-700 mb-1">Parent Team</label>
                <select
                  value={teamId}
                  onChange={(e) => setTeamId(Number(e.target.value))}
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm bg-white"
                >
                  <option value="" disabled>Select team</option>
                  {teamsData?.data?.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Project Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="e.g. Mobile App Redesign"
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={3}
                  placeholder="Goals and milestones..."
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">Start Date</label>
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">Due Date</label>
                  <input
                    type="date"
                    value={dueDate}
                    onChange={(e) => setDueDate(e.target.value)}
                    className="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs"
                  />
                </div>
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
                disabled={!name.trim() || !teamId || createProjectMutation.isPending}
                onClick={() =>
                  createProjectMutation.mutate({
                    team_id: Number(teamId),
                    name,
                    description: description.trim() || undefined,
                    start_date: startDate || undefined,
                    due_date: dueDate || undefined,
                  })
                }
                className="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50"
              >
                {createProjectMutation.isPending ? 'Creating...' : 'Create Project'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

