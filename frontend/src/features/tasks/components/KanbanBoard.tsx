import React, { useState } from 'react';
import { Task } from '../types/tasks.types';
import { TaskStatus } from '@/types/enums';
import { TaskCard } from './TaskCard';
import { tasksService } from '../api/tasksService';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { TaskFlowApiError } from '@/api/client';
import { AlertCircle, Plus } from 'lucide-react';

interface KanbanBoardProps {
  tasks: Task[];
  projectId: number;
  onSelectTask: (task: Task) => void;
  onOpenCreateTask: (status: TaskStatus) => void;
}

const COLUMNS: { id: TaskStatus; title: string; color: string }[] = [
  { id: 'TODO', title: 'To Do', color: 'bg-slate-500' },
  { id: 'IN_PROGRESS', title: 'In Progress', color: 'bg-blue-600' },
  { id: 'IN_REVIEW', title: 'In Review', color: 'bg-purple-600' },
  { id: 'DONE', title: 'Completed', color: 'bg-emerald-600' },
  { id: 'CANCELLED', title: 'Cancelled', color: 'bg-rose-500' },
];

export const KanbanBoard: React.FC<KanbanBoardProps> = ({
  tasks,
  projectId,
  onSelectTask,
  onOpenCreateTask,
}) => {
  const queryClient = useQueryClient();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const statusMutation = useMutation({
    mutationFn: (values: { taskId: number; status: TaskStatus }) =>
      tasksService.changeStatus(values.taskId, { status: values.status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['project-tasks', projectId] });
      setErrorMessage(null);
    },
    onError: (err: unknown) => {
      if (err instanceof TaskFlowApiError) {
        setErrorMessage(err.message);
      } else {
        setErrorMessage('Failed to transition task status.');
      }
    },
  });

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDrop = (status: TaskStatus, e: React.DragEvent) => {
    e.preventDefault();
    const taskIdStr = e.dataTransfer.getData('taskId');
    if (!taskIdStr) return;
    const taskId = Number(taskIdStr);

    const task = tasks.find((t) => t.id === taskId);
    if (!task || task.status === status) return;

    statusMutation.mutate({ taskId, status });
  };

  return (
    <div className="space-y-4">
      {errorMessage && (
        <div className="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center justify-between">
          <div className="flex items-center gap-2">
            <AlertCircle className="w-4 h-4 shrink-0" />
            <span>{errorMessage}</span>
          </div>
          <button
            onClick={() => setErrorMessage(null)}
            className="text-red-500 hover:text-red-700 font-bold px-2 py-0.5"
          >
            ✕
          </button>
        </div>
      )}

      <div className="flex gap-4 overflow-x-auto pb-6 min-h-[calc(100vh-280px)]">
        {COLUMNS.map((column) => {
          const columnTasks = tasks.filter((t) => t.status === column.id);

          return (
            <div
              key={column.id}
              onDragOver={handleDragOver}
              onDrop={(e) => handleDrop(column.id, e)}
              className="w-80 shrink-0 bg-slate-100/70 border border-slate-200/80 rounded-2xl flex flex-col max-h-[calc(100vh-280px)]"
            >
              {/* Column Header */}
              <div className="p-3.5 border-b border-slate-200/70 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className={`w-2 h-2 rounded-full ${column.color}`} />
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    {column.title}
                  </h3>
                  <span className="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-slate-600 shadow-2xs">
                    {columnTasks.length}
                  </span>
                </div>
                <button
                  onClick={() => onOpenCreateTask(column.id)}
                  className="p-1 rounded-md text-slate-400 hover:text-blue-600 hover:bg-white transition-colors"
                  title="Add Task"
                >
                  <Plus className="w-4 h-4" />
                </button>
              </div>

              {/* Tasks List / Drop Area */}
              <div className="p-3 flex-1 overflow-y-auto space-y-3">
                {columnTasks.map((task) => (
                  <div
                    key={task.id}
                    draggable
                    onDragStart={(e) => {
                      e.dataTransfer.setData('taskId', String(task.id));
                    }}
                  >
                    <TaskCard task={task} onClick={() => onSelectTask(task)} />
                  </div>
                ))}

                {columnTasks.length === 0 && (
                  <div className="h-32 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center text-xs text-slate-400">
                    Drop tasks here
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

