import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Task, TaskComment, TaskStatusHistory } from '../types/tasks.types';
import { tasksService } from '../api/tasksService';
import { projectsService } from '@/features/projects/api/projectsService';
import { TaskPriority, TaskStatus } from '@/types/enums';
import {
  X,
  Calendar,
  Users,
  CheckSquare,
  Network,
  MessageSquare,
  Paperclip,
  History,
  Trash2,
  Download,
  Send,
  Plus,
  AlertCircle,
} from 'lucide-react';
import { TaskFlowApiError } from '@/api/client';

interface TaskDrawerProps {
  task: Task;
  onClose: () => void;
}

export const TaskDrawer: React.FC<TaskDrawerProps> = ({ task, onClose }) => {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<'details' | 'comments' | 'attachments' | 'history'>('details');

  // Form states
  const [title, setTitle] = useState(task.title);
  const [description, setDescription] = useState(task.description || '');
  const [priority, setPriority] = useState<TaskPriority>(task.priority);
  const [newSubtaskTitle, setNewSubtaskTitle] = useState('');
  const [dependsOnTaskId, setDependsOnTaskId] = useState('');
  const [newComment, setNewComment] = useState('');
  const [drawerError, setDrawerError] = useState<string | null>(null);

  // Load project members for assignee selection
  const { data: members } = useQuery({
    queryKey: ['project-members', task.project_id],
    queryFn: () => projectsService.listMembers(task.project_id),
  });

  // Load project tasks for dependency selection
  const { data: projectTasks } = useQuery({
    queryKey: ['project-tasks', task.project_id],
    queryFn: () => tasksService.listProjectTasks(task.project_id),
  });

  // Comments Query
  const { data: commentsData } = useQuery({
    queryKey: ['task-comments', task.id],
    queryFn: () => tasksService.listComments(task.id),
  });

  // History Query
  const { data: historyData } = useQuery({
    queryKey: ['task-history', task.id],
    queryFn: () => tasksService.getStatusHistory(task.id),
    enabled: activeTab === 'history',
  });

  // Mutations
  const updateTaskMutation = useMutation({
    mutationFn: (values: { title?: string; description?: string; priority?: TaskPriority }) =>
      tasksService.updateTask(task.id, values),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
      setDrawerError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setDrawerError(err.message);
    },
  });

  const statusMutation = useMutation({
    mutationFn: (newStatus: TaskStatus) => tasksService.changeStatus(task.id, { status: newStatus }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
      queryClient.invalidateQueries({ queryKey: ['task-history', task.id] });
      setDrawerError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setDrawerError(err.message);
    },
  });

  const createSubtaskMutation = useMutation({
    mutationFn: (subtaskTitle: string) =>
      tasksService.createTask(task.project_id, {
        title: subtaskTitle,
        parent_task_id: task.id,
        priority: 'MEDIUM',
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
      setNewSubtaskTitle('');
      setDrawerError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setDrawerError(err.message);
    },
  });

  const addDependencyMutation = useMutation({
    mutationFn: (prereqId: number) => tasksService.addDependency(task.id, prereqId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
      setDependsOnTaskId('');
      setDrawerError(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setDrawerError(err.message);
    },
  });

  const addCommentMutation = useMutation({
    mutationFn: (body: string) => tasksService.addComment(task.id, body),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['task-comments', task.id] });
      setNewComment('');
    },
  });

  const deleteCommentMutation = useMutation({
    mutationFn: (commentId: number) => tasksService.deleteComment(commentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['task-comments', task.id] });
    },
  });

  const uploadFileMutation = useMutation({
    mutationFn: (file: File) => tasksService.uploadAttachment(task.id, file),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) setDrawerError(err.message);
    },
  });

  const deleteAttachmentMutation = useMutation({
    mutationFn: (attachmentId: number) => tasksService.deleteAttachment(task.id, attachmentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
    },
  });

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      uploadFileMutation.mutate(e.target.files[0]);
    }
  };

  return (
    <div className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl bg-white shadow-2xl border-l border-slate-200 flex flex-col animate-in slide-in-from-right duration-200">
      {/* Drawer Header */}
      <div className="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <select
            value={task.status}
            onChange={(e) => statusMutation.mutate(e.target.value as TaskStatus)}
            className="text-xs font-bold px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 uppercase tracking-wide focus:outline-none"
          >
            <option value="TODO">TODO</option>
            <option value="IN_PROGRESS">IN_PROGRESS</option>
            <option value="IN_REVIEW">IN_REVIEW</option>
            <option value="DONE">DONE</option>
            <option value="CANCELLED">CANCELLED</option>
          </select>

          <span className="text-xs font-mono text-slate-400">Task #{task.id}</span>
        </div>

        <button
          onClick={onClose}
          className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>
      </div>

      {drawerError && (
        <div className="mx-6 mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
          <AlertCircle className="w-4 h-4 shrink-0" />
          <span>{drawerError}</span>
        </div>
      )}

      {/* Tabs Bar */}
      <div className="px-6 border-b border-slate-100 flex gap-4 text-xs font-semibold text-slate-500">
        <button
          onClick={() => setActiveTab('details')}
          className={`py-3 border-b-2 transition-colors ${
            activeTab === 'details' ? 'border-blue-600 text-blue-600' : 'border-transparent hover:text-slate-700'
          }`}
        >
          Details & Subtasks
        </button>
        <button
          onClick={() => setActiveTab('comments')}
          className={`py-3 border-b-2 transition-colors flex items-center gap-1.5 ${
            activeTab === 'comments' ? 'border-blue-600 text-blue-600' : 'border-transparent hover:text-slate-700'
          }`}
        >
          <MessageSquare className="w-3.5 h-3.5" />
          <span>Comments</span>
        </button>
        <button
          onClick={() => setActiveTab('attachments')}
          className={`py-3 border-b-2 transition-colors flex items-center gap-1.5 ${
            activeTab === 'attachments' ? 'border-blue-600 text-blue-600' : 'border-transparent hover:text-slate-700'
          }`}
        >
          <Paperclip className="w-3.5 h-3.5" />
          <span>Files</span>
        </button>
        <button
          onClick={() => setActiveTab('history')}
          className={`py-3 border-b-2 transition-colors flex items-center gap-1.5 ${
            activeTab === 'history' ? 'border-blue-600 text-blue-600' : 'border-transparent hover:text-slate-700'
          }`}
        >
          <History className="w-3.5 h-3.5" />
          <span>Audit History</span>
        </button>
      </div>

      {/* Drawer Body */}
      <div className="flex-1 overflow-y-auto p-6 space-y-6">
        {activeTab === 'details' && (
          <>
            {/* Title & Priority */}
            <div className="space-y-4">
              <div>
                <input
                  type="text"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  onBlur={() => {
                    if (title.trim() && title !== task.title) {
                      updateTaskMutation.mutate({ title });
                    }
                  }}
                  className="w-full text-lg font-bold text-slate-900 border-none p-0 focus:outline-none focus:ring-0"
                  placeholder="Task title..."
                />
              </div>

              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  <span className="text-xs text-slate-500 font-medium">Priority:</span>
                  <select
                    value={priority}
                    onChange={(e) => {
                      const newPri = e.target.value as TaskPriority;
                      setPriority(newPri);
                      updateTaskMutation.mutate({ priority: newPri });
                    }}
                    className="text-xs font-semibold px-2.5 py-1 rounded-md border border-slate-200 bg-white"
                  >
                    <option value="LOW">LOW</option>
                    <option value="MEDIUM">MEDIUM</option>
                    <option value="HIGH">HIGH</option>
                    <option value="URGENT">URGENT</option>
                  </select>
                </div>

                {task.due_at && (
                  <div className="flex items-center gap-1 text-xs text-slate-500">
                    <Calendar className="w-3.5 h-3.5" />
                    <span>Due {new Date(task.due_at).toLocaleDateString()}</span>
                  </div>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Description</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  onBlur={() => {
                    if (description !== (task.description || '')) {
                      updateTaskMutation.mutate({ description });
                    }
                  }}
                  rows={4}
                  placeholder="Add a detailed description..."
                  className="w-full p-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />
              </div>
            </div>

            {/* Subtasks Section */}
            <div className="pt-4 border-t border-slate-100 space-y-3">
              <div className="flex items-center justify-between">
                <h4 className="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider">
                  <CheckSquare className="w-4 h-4 text-blue-600" />
                  Subtasks Checklist
                </h4>
                <span className="text-xs text-slate-400">
                  {task.subtasks?.filter((s) => s.status === 'DONE').length || 0}/
                  {task.subtasks?.length || 0}
                </span>
              </div>

              <div className="space-y-2">
                {task.subtasks && task.subtasks.length > 0 ? (
                  task.subtasks.map((sub) => (
                    <div
                      key={sub.id}
                      className="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 bg-slate-50/70 text-xs"
                    >
                      <div className="flex items-center gap-2">
                        <input
                          type="checkbox"
                          checked={sub.status === 'DONE'}
                          onChange={() =>
                            tasksService.changeStatus(sub.id, {
                              status: sub.status === 'DONE' ? 'TODO' : 'DONE',
                            }).then(() => {
                              queryClient.invalidateQueries({ queryKey: ['project-tasks', task.project_id] });
                            })
                          }
                          className="rounded border-slate-300 text-blue-600"
                        />
                        <span className={sub.status === 'DONE' ? 'line-through text-slate-400' : 'text-slate-800'}>
                          {sub.title}
                        </span>
                      </div>
                      <span className="text-[10px] font-mono text-slate-400">#{sub.id}</span>
                    </div>
                  ))
                ) : (
                  <p className="text-xs text-slate-400 py-1">No subtasks added yet.</p>
                )}
              </div>

              <div className="flex items-center gap-2 mt-2">
                <input
                  type="text"
                  value={newSubtaskTitle}
                  onChange={(e) => setNewSubtaskTitle(e.target.value)}
                  placeholder="New subtask title..."
                  className="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs"
                />
                <button
                  disabled={!newSubtaskTitle.trim() || createSubtaskMutation.isPending}
                  onClick={() => createSubtaskMutation.mutate(newSubtaskTitle)}
                  className="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 disabled:opacity-50"
                >
                  Add
                </button>
              </div>
            </div>

            {/* Prerequisite Dependencies Section */}
            <div className="pt-4 border-t border-slate-100 space-y-3">
              <h4 className="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider">
                <Network className="w-4 h-4 text-purple-600" />
                Prerequisite Dependencies
              </h4>
              <p className="text-[11px] text-slate-500">
                This task cannot be marked completed until all prerequisites are completed.
              </p>

              <div className="flex items-center gap-2">
                <select
                  value={dependsOnTaskId}
                  onChange={(e) => setDependsOnTaskId(e.target.value)}
                  className="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white"
                >
                  <option value="">Select prerequisite task...</option>
                  {projectTasks?.data
                    ?.filter((t) => t.id !== task.id)
                    .map((t) => (
                      <option key={t.id} value={t.id}>
                        #{t.id} - {t.title} ({t.status})
                      </option>
                    ))}
                </select>
                <button
                  disabled={!dependsOnTaskId || addDependencyMutation.isPending}
                  onClick={() => addDependencyMutation.mutate(Number(dependsOnTaskId))}
                  className="px-3 py-1.5 rounded-lg bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700 disabled:opacity-50"
                >
                  Add Prerequisite
                </button>
              </div>
            </div>
          </>
        )}

        {/* Comments Tab */}
        {activeTab === 'comments' && (
          <div className="space-y-4">
            <div className="space-y-3">
              {commentsData?.data && commentsData.data.length > 0 ? (
                commentsData.data.map((c: TaskComment) => (
                  <div key={c.id} className="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <div className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center">
                          {c.user?.name?.charAt(0) || 'U'}
                        </div>
                        <span className="text-xs font-bold text-slate-900">{c.user?.name}</span>
                        <span className="text-[10px] text-slate-400">
                          {new Date(c.created_at).toLocaleString()}
                        </span>
                      </div>
                      {c.helpers?.can_delete && (
                        <button
                          onClick={() => deleteCommentMutation.mutate(c.id)}
                          className="text-slate-400 hover:text-red-600"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      )}
                    </div>
                    <p className="text-xs text-slate-700 whitespace-pre-wrap">{c.body}</p>
                  </div>
                ))
              ) : (
                <p className="text-center text-xs text-slate-400 py-6">No comments yet. Start the discussion!</p>
              )}
            </div>

            <div className="flex items-center gap-2 pt-2">
              <input
                type="text"
                value={newComment}
                onChange={(e) => setNewComment(e.target.value)}
                placeholder="Write a comment..."
                className="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 text-xs"
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && newComment.trim()) {
                    addCommentMutation.mutate(newComment);
                  }
                }}
              />
              <button
                disabled={!newComment.trim() || addCommentMutation.isPending}
                onClick={() => addCommentMutation.mutate(newComment)}
                className="p-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
              >
                <Send className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}

        {/* Attachments Tab */}
        {activeTab === 'attachments' && (
          <div className="space-y-4">
            <div className="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-blue-400 transition-colors">
              <Paperclip className="w-8 h-8 text-slate-400 mx-auto mb-2" />
              <label className="text-xs font-semibold text-blue-600 hover:text-blue-700 cursor-pointer">
                <span>Upload a document or file</span>
                <input type="file" className="hidden" onChange={handleFileUpload} />
              </label>
              <p className="text-[11px] text-slate-400 mt-1">Files are stored securely with authorized access.</p>
            </div>
          </div>
        )}

        {/* Audit History Tab */}
        {activeTab === 'history' && (
          <div className="space-y-3">
            {historyData && historyData.length > 0 ? (
              historyData.map((h: TaskStatusHistory) => (
                <div key={h.id} className="flex items-start gap-3 p-3 rounded-lg border border-slate-100 text-xs">
                  <div className="w-2 h-2 rounded-full bg-blue-600 mt-1.5 shrink-0" />
                  <div className="flex-1">
                    <p className="text-slate-800">
                      <span className="font-semibold">{h.user?.name || 'User'}</span> transitioned status from{' '}
                      <span className="font-mono font-bold text-slate-600">{h.old_status}</span> to{' '}
                      <span className="font-mono font-bold text-blue-600">{h.new_status}</span>
                    </p>
                    {h.reason && <p className="text-[11px] text-slate-500 italic mt-0.5">&quot;{h.reason}&quot;</p>}
                    <span className="text-[10px] text-slate-400 mt-1 block">
                      {new Date(h.created_at).toLocaleString()}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-center text-xs text-slate-400 py-6">No status history found.</p>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

