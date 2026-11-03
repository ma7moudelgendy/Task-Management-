import React, { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { projectsService } from '../api/projectsService';
import { teamsService } from '@/features/teams/api/teamsService';
import { tasksService } from '@/features/tasks/api/tasksService';
import { KanbanBoard } from '@/features/tasks/components/KanbanBoard';
import { TaskDrawer } from '@/features/tasks/components/TaskDrawer';
import { Task } from '@/features/tasks/types/tasks.types';
import { ProjectRole, TaskPriority, TaskStatus } from '@/types/enums';
import {
  FolderKanban,
  Kanban,
  List,
  BarChart3,
  Users,
  Plus,
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Trash2,
  X,
  AlertCircle,
} from 'lucide-react';
import { TaskFlowApiError } from '@/api/client';

export const ProjectDetailView: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const projectId = Number(id);
  const queryClient = useQueryClient();

  const [activeTab, setActiveTab] = useState<'board' | 'list' | 'analytics' | 'members'>('board');
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);

  // Create Task Modal state
  const [createTaskModalOpen, setCreateTaskModalOpen] = useState(false);
  const [taskTitle, setTaskTitle] = useState('');
  const [taskDesc, setTaskDesc] = useState('');
  const [taskPriority, setTaskPriority] = useState<TaskPriority>('MEDIUM');
  const [taskDueAt, setTaskDueAt] = useState('');
  const [taskAssigneeIds, setTaskAssigneeIds] = useState<number[]>([]);
  const [createTaskError, setCreateTaskError] = useState<string | null>(null);

  // Add Member state
  const [newMemberUserId, setNewMemberUserId] = useState('');
  const [newMemberRole, setNewMemberRole] = useState<ProjectRole>('MEMBER');
  const [memberError, setMemberError] = useState<string | null>(null);

  // Queries
  const { data: project, isLoading: projectLoading } = useQuery({
    queryKey: ['project', projectId],
    queryFn: () => projectsService.getProject(projectId),
    enabled: Boolean(projectId),
  });

  const { data: tasksData, isLoading: tasksLoading } = useQuery({
    queryKey: ['project-tasks', projectId],
    queryFn: () => tasksService.listProjectTasks(projectId, { per_page: 100 }),
    enabled: Boolean(projectId),
  });

  const { data: membersData, isLoading: membersLoading } = useQuery({
    queryKey: ['project-members', projectId],
    queryFn: () => projectsService.listMembers(projectId),
    enabled: Boolean(projectId),
  });

  const { data: teamMembersData } = useQuery({
    queryKey: ['team-members', project?.team_id],
    queryFn: () => (project?.team_id ? teamsService.listMembers(project.team_id) : Promise.resolve([])),
    enabled: Boolean(project?.team_id),
  });

  const { data: analyticsData } = useQuery({
    queryKey: ['project-analytics', projectId],
    queryFn: () => projectsService.getAnalytics(projectId),
    enabled: Boolean(projectId) && activeTab === 'analytics',
  });

  // Mutations
  const createTaskMutation = useMutation({
    mutationFn: (values: {
      title: string;
      description?: string;
      priority: TaskPriority;
      due_at?: string;
      assignee_ids?: number[];
    }) => tasksService.createTask(projectId, values),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', projectId] });
      setCreateTaskModalOpen(false);
      setTaskTitle('');
      setTaskDesc('');
      setTaskPriority('MEDIUM');
      setTaskDueAt('');
      setTaskAssigneeIds([]);
      setCreateTaskError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setCreateTaskError(err.message);
    },
  });

  const addMemberMutation = useMutation({
    mutationFn: (values: { userId: number; role: ProjectRole }) =>
      projectsService.addMember(projectId, { user_id: values.userId, role: values.role }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-members', projectId] });
      queryClient.invalidateQueries({ queryKey: ['project', projectId] });
      setNewMemberUserId('');
      setMemberError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setMemberError(err.message);
      } else {
        setMemberError('Failed to add member to project.');
      }
    },
  });

  const updateMemberRoleMutation = useMutation({
    mutationFn: (values: { userId: number; role: ProjectRole }) =>
      projectsService.updateMemberRole(projectId, values.userId, values.role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-members', projectId] });
      setMemberError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setMemberError(err.message);
      } else {
        setMemberError('Failed to update member role.');
      }
    },
  });

  const removeMemberMutation = useMutation({
    mutationFn: (userId: number) => projectsService.removeMember(projectId, userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-members', projectId] });
      queryClient.invalidateQueries({ queryKey: ['project', projectId] });
      setMemberError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setMemberError(err.message);
      } else {
        setMemberError('Failed to remove member from project.');
      }
    },
  });

  if (projectLoading) {
    return (
      <div className="space-y-4 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-64" />
        <div className="h-12 bg-slate-200 rounded-xl" />
        <div className="h-96 bg-slate-200 rounded-2xl" />
      </div>
    );
  }

  if (!project) {
    return (
      <div className="p-8 text-center bg-white rounded-2xl border border-slate-200">
        <FolderKanban className="w-10 h-10 text-slate-400 mx-auto mb-2" />
        <h3 className="text-base font-semibold text-slate-800">Project Not Found</h3>
        <Link to="/projects" className="mt-3 inline-block text-xs font-semibold text-blue-600">
          ← Back to Projects
        </Link>
      </div>
    );
  }

  const tasks = tasksData?.data || [];
  const existingMemberUserIds = new Set(membersData?.map((m) => m.user_id) || []);
  const availableTeamMembers = teamMembersData?.filter((tm) => !existingMemberUserIds.has(tm.user_id)) || [];

  return (
    <div className="space-y-6">
      {/* Header Bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <Link
            to="/projects"
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 mb-2"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>All Projects</span>
          </Link>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">{project.name}</h1>
            <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
              {project.status}
            </span>
          </div>
          {project.description && (
            <p className="text-xs text-slate-500 mt-1">{project.description}</p>
          )}
        </div>

        <button
          onClick={() => setCreateTaskModalOpen(true)}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>Create Task</span>
        </button>
      </div>

      {/* Tabs */}
      <div className="flex items-center gap-2 border-b border-slate-200">
        <button
          onClick={() => setActiveTab('board')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'board'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          <Kanban className="w-4 h-4" />
          <span>Kanban Board</span>
        </button>

        <button
          onClick={() => setActiveTab('list')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'list'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          <List className="w-4 h-4" />
          <span>Task Table</span>
        </button>

        <button
          onClick={() => setActiveTab('analytics')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'analytics'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          <BarChart3 className="w-4 h-4" />
          <span>Analytics</span>
        </button>

        <button
          onClick={() => setActiveTab('members')}
          className={`flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors ${
            activeTab === 'members'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          }`}
        >
          <Users className="w-4 h-4" />
          <span>Members ({membersData?.length ?? project.member_count ?? 0})</span>
        </button>
      </div>

      {/* Tab: Kanban Board */}
      {activeTab === 'board' && (
        <KanbanBoard
          tasks={tasks}
          projectId={projectId}
          onSelectTask={(t) => setSelectedTask(t)}
          onOpenCreateTask={() => setCreateTaskModalOpen(true)}
        />
      )}

      {/* Tab: Task Table */}
      {activeTab === 'list' && (
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase">
              <tr>
                <th className="py-3 px-4">Task</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4">Priority</th>
                <th className="py-3 px-4">Due Date</th>
                <th className="py-3 px-4">Assignees</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {tasks.map((task) => (
                <tr
                  key={task.id}
                  onClick={() => setSelectedTask(task)}
                  className="hover:bg-slate-50 cursor-pointer transition-colors"
                >
                  <td className="py-3 px-4 font-semibold text-slate-900">{task.title}</td>
                  <td className="py-3 px-4">
                    <span className="font-semibold px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700">
                      {task.status}
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    <span className="font-semibold text-[10px] text-blue-600">{task.priority}</span>
                  </td>
                  <td className="py-3 px-4 text-slate-500">
                    {task.due_at ? new Date(task.due_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="py-3 px-4">
                    <div className="flex -space-x-1">
                      {task.assignees?.map((a) => (
                        <div
                          key={a.id}
                          className="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center border border-white"
                        >
                          {a.name.charAt(0)}
                        </div>
                      ))}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Tab: Project Analytics */}
      {activeTab === 'analytics' && analyticsData && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <span className="text-xs font-semibold text-slate-500">Progress</span>
              <div className="text-3xl font-extrabold text-blue-600 mt-2">
                {analyticsData.completion_percentage}%
              </div>
              <span className="text-xs text-slate-400 mt-1 block">
                {analyticsData.completed_tasks} of {analyticsData.total_tasks} completed
              </span>
            </div>

            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <span className="text-xs font-semibold text-slate-500">Total Active Tasks</span>
              <div className="text-3xl font-extrabold text-slate-900 mt-2">
                {analyticsData.total_tasks}
              </div>
            </div>

            <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
              <span className="text-xs font-semibold text-red-600">Overdue Tasks</span>
              <div className="text-3xl font-extrabold text-red-600 mt-2">
                {analyticsData.overdue_count}
              </div>
            </div>
          </div>

          {/* Member Workload Distribution */}
          <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <h3 className="text-sm font-bold text-slate-900 mb-4">Member Workload Distribution</h3>
            <div className="divide-y divide-slate-100 text-xs">
              {analyticsData.assignee_workload.map((w) => (
                <div key={w.user_id} className="py-3 flex items-center justify-between">
                  <div className="font-semibold text-slate-800">{w.user_name}</div>
                  <div className="flex items-center gap-4 text-slate-500">
                    <span>{w.total_assigned} Total</span>
                    <span className="text-emerald-600 font-semibold">{w.completed} Done</span>
                    <span className="text-blue-600 font-semibold">{w.in_progress} In Progress</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Tab: Project Members */}
      {activeTab === 'members' && (
        <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
              <h3 className="text-sm font-bold text-slate-900">Project Members</h3>
              <p className="text-xs text-slate-500">Manage who has access to this project and their permissions.</p>
            </div>

            {project.helpers.can_manage_members && (
              <div className="flex flex-wrap items-center gap-2">
                {availableTeamMembers.length > 0 && (
                  <select
                    value={newMemberUserId}
                    onChange={(e) => {
                      setNewMemberUserId(e.target.value);
                      setMemberError(null);
                    }}
                    className="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white text-slate-700"
                  >
                    <option value="">Select from team...</option>
                    {availableTeamMembers.map((tm) => (
                      <option key={tm.user_id} value={tm.user_id}>
                        {tm.name || tm.user?.name || `User #${tm.user_id}`}
                      </option>
                    ))}
                  </select>
                )}

                <input
                  type="number"
                  value={newMemberUserId}
                  onChange={(e) => {
                    setNewMemberUserId(e.target.value);
                    setMemberError(null);
                  }}
                  placeholder="Or User ID..."
                  className="w-28 px-3 py-1.5 rounded-lg border border-slate-200 text-xs"
                />

                <select
                  value={newMemberRole}
                  onChange={(e) => setNewMemberRole(e.target.value as ProjectRole)}
                  className="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white"
                >
                  <option value="VIEWER">VIEWER</option>
                  <option value="MEMBER">MEMBER</option>
                  <option value="MANAGER">MANAGER</option>
                  <option value="OWNER">OWNER</option>
                </select>

                <button
                  disabled={!newMemberUserId || addMemberMutation.isPending}
                  onClick={() =>
                    addMemberMutation.mutate({
                      userId: Number(newMemberUserId),
                      role: newMemberRole,
                    })
                  }
                  className="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50 transition-colors"
                >
                  {addMemberMutation.isPending ? 'Adding...' : 'Add Member'}
                </button>
              </div>
            )}
          </div>

          {memberError && (
            <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{memberError}</span>
            </div>
          )}

          {membersLoading ? (
            <div className="py-8 text-center text-xs text-slate-400 animate-pulse">
              Loading project members...
            </div>
          ) : membersData && membersData.length > 0 ? (
            <div className="divide-y divide-slate-100">
              {membersData.map((m) => (
                <div key={m.id || m.user_id} className="py-3 flex items-center justify-between text-xs">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-blue-50 text-blue-700 font-bold text-xs flex items-center justify-center border border-blue-100">
                      {(m.name || m.user?.name)?.charAt(0).toUpperCase() || 'U'}
                    </div>
                    <div>
                      <span className="font-bold text-slate-900 block">
                        {m.name || m.user?.name || `User #${m.user_id}`}
                      </span>
                      <span className="text-slate-400 text-[11px]">
                        {m.email || m.user?.email || `ID: ${m.user_id}`}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    {project.helpers.can_change_role ? (
                      <select
                        value={m.role}
                        onChange={(e) =>
                          updateMemberRoleMutation.mutate({
                            userId: m.user_id,
                            role: e.target.value as ProjectRole,
                          })
                        }
                        className="text-xs font-semibold px-2.5 py-1 rounded-md border border-slate-200 bg-white"
                      >
                        <option value="VIEWER">VIEWER</option>
                        <option value="MEMBER">MEMBER</option>
                        <option value="MANAGER">MANAGER</option>
                        <option value="OWNER">OWNER</option>
                      </select>
                    ) : (
                      <span className="font-semibold px-2.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                        {m.role}
                      </span>
                    )}

                    {project.helpers.can_manage_members && (
                      <button
                        onClick={() => {
                          if (confirm(`Remove ${m.name || 'this member'} from the project?`)) {
                            removeMemberMutation.mutate(m.user_id);
                          }
                        }}
                        className="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors"
                        title="Remove Member"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-8 text-center text-xs text-slate-400">
              No members found in this project.
            </div>
          )}
        </div>
      )}

      {/* Task Drawer */}
      {selectedTask && (
        <TaskDrawer task={selectedTask} onClose={() => setSelectedTask(null)} />
      )}

      {/* Create Task Modal */}
      {createTaskModalOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <h3 className="text-base font-bold text-slate-900">Create New Task</h3>
              <button
                onClick={() => setCreateTaskModalOpen(false)}
                className="text-slate-400 hover:text-slate-600 p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {createTaskError && (
              <div className="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-xs">
                {createTaskError}
              </div>
            )}

            <div className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Title</label>
                <input
                  type="text"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  placeholder="Task title..."
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                <textarea
                  value={taskDesc}
                  onChange={(e) => setTaskDesc(e.target.value)}
                  rows={3}
                  placeholder="Details and criteria..."
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">Priority</label>
                  <select
                    value={taskPriority}
                    onChange={(e) => setTaskPriority(e.target.value as TaskPriority)}
                    className="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"
                  >
                    <option value="LOW">LOW</option>
                    <option value="MEDIUM">MEDIUM</option>
                    <option value="HIGH">HIGH</option>
                    <option value="URGENT">URGENT</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">Due Date</label>
                  <input
                    type="date"
                    value={taskDueAt}
                    onChange={(e) => setTaskDueAt(e.target.value)}
                    className="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs"
                  />
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end gap-2">
              <button
                onClick={() => setCreateTaskModalOpen(false)}
                className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100"
              >
                Cancel
              </button>
              <button
                disabled={!taskTitle.trim() || createTaskMutation.isPending}
                onClick={() =>
                  createTaskMutation.mutate({
                    title: taskTitle,
                    description: taskDesc.trim() || undefined,
                    priority: taskPriority,
                    due_at: taskDueAt ? `${taskDueAt} 23:59:59` : undefined,
                    assignee_ids: taskAssigneeIds,
                  })
                }
                className="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold disabled:opacity-50"
              >
                {createTaskMutation.isPending ? 'Creating...' : 'Create Task'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

