import React from 'react';
import { Task } from '../types/tasks.types';
import { TaskPriority } from '@/types/enums';
import { Calendar, CheckSquare, User as UserIcon } from 'lucide-react';

interface TaskCardProps {
  task: Task;
  onClick: () => void;
}

const priorityBadgeColors: Record<TaskPriority, string> = {
  LOW: 'bg-slate-100 text-slate-700 border-slate-200',
  MEDIUM: 'bg-blue-50 text-blue-700 border-blue-200',
  HIGH: 'bg-amber-50 text-amber-700 border-amber-200',
  URGENT: 'bg-red-50 text-red-700 border-red-200',
};

export const TaskCard: React.FC<TaskCardProps> = ({ task, onClick }) => {
  const isOverdue =
    task.due_at &&
    task.status !== 'DONE' &&
    task.status !== 'CANCELLED' &&
    new Date(task.due_at) < new Date();

  return (
    <div
      onClick={onClick}
      className="group bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all cursor-pointer select-none space-y-3"
    >
      <div className="flex items-center justify-between gap-2">
        <span
          className={`text-[10px] font-bold px-2 py-0.5 rounded-md border tracking-wide uppercase ${
            priorityBadgeColors[task.priority]
          }`}
        >
          {task.priority}
        </span>
        <span className="text-[10px] font-mono text-slate-400">#{task.id}</span>
      </div>

      <h4 className="text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-2">
        {task.title}
      </h4>

      {task.description && (
        <p className="text-xs text-slate-500 line-clamp-2">{task.description}</p>
      )}

      {/* Subtasks summary */}
      {task.subtasks && task.subtasks.length > 0 && (
        <div className="flex items-center gap-1.5 text-xs text-slate-500">
          <CheckSquare className="w-3.5 h-3.5 text-slate-400" />
          <span>
            {task.subtasks.filter((s) => s.status === 'DONE').length}/{task.subtasks.length} subtasks
          </span>
        </div>
      )}

      {/* Footer: Due date & Assignees */}
      <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
        <div className="flex items-center gap-1 text-[11px]">
          <Calendar className={`w-3.5 h-3.5 ${isOverdue ? 'text-red-500' : 'text-slate-400'}`} />
          <span className={isOverdue ? 'text-red-600 font-semibold' : 'text-slate-500'}>
            {task.due_at ? new Date(task.due_at).toLocaleDateString() : 'No due date'}
          </span>
        </div>

        <div className="flex items-center -space-x-1.5 overflow-hidden">
          {task.assignees && task.assignees.length > 0 ? (
            task.assignees.slice(0, 3).map((a) => (
              <div
                key={a.id}
                title={a.name}
                className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 border-2 border-white flex items-center justify-center text-[10px] font-bold"
              >
                {a.name.charAt(0)}
              </div>
            ))
          ) : (
            <div className="w-6 h-6 rounded-full bg-slate-100 text-slate-400 border border-slate-200 flex items-center justify-center">
              <UserIcon className="w-3 h-3" />
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

