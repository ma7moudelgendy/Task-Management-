import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { dashboardService } from '../api/dashboardService';
import {
  CheckCircle2,
  Clock,
  AlertTriangle,
  ListTodo,
  Calendar,
  Activity,
  ArrowRight,
  ShieldAlert,
} from 'lucide-react';
import { Link } from 'react-router-dom';

const priorityBadgeColors: Record<string, string> = {
  LOW: 'bg-slate-100 text-slate-700 border-slate-200',
  MEDIUM: 'bg-blue-50 text-blue-700 border-blue-200',
  HIGH: 'bg-amber-50 text-amber-700 border-amber-200',
  URGENT: 'bg-red-50 text-red-700 border-red-200',
};

const statusBadgeColors: Record<string, string> = {
  TODO: 'bg-slate-100 text-slate-700',
  IN_PROGRESS: 'bg-blue-100 text-blue-700',
  IN_REVIEW: 'bg-purple-100 text-purple-700',
  DONE: 'bg-emerald-100 text-emerald-700',
  CANCELLED: 'bg-rose-100 text-rose-700',
};

export const DashboardView: React.FC = () => {
  const { data, isLoading, error } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => dashboardService.getDashboard(),
  });

  if (isLoading) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="h-8 bg-slate-200 rounded-md w-48" />
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 bg-slate-200 rounded-xl" />
          ))}
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="h-64 bg-slate-200 rounded-xl" />
          <div className="h-64 bg-slate-200 rounded-xl" />
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="p-8 text-center bg-white rounded-2xl border border-red-100 shadow-xs">
        <ShieldAlert className="w-10 h-10 text-red-500 mx-auto mb-3" />
        <h3 className="text-base font-semibold text-slate-900">Failed to load dashboard metrics</h3>
        <p className="text-sm text-slate-500 mt-1">Please ensure your Laravel API is running and reachable.</p>
      </div>
    );
  }

  const { stats, overdue_tasks, upcoming_tasks, recent_activity } = data;

  return (
    <div className="space-y-8">
      {/* Page Title */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Personal Dashboard</h1>
        <p className="text-sm text-slate-500 mt-1">
          Real-time snapshot of your workload, deadlines, and recent updates.
        </p>
      </div>

      {/* KPI Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Assigned */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned to Me</span>
            <div className="text-2xl font-bold text-slate-900 mt-1">{stats?.total_assigned ?? 0}</div>
            <span className="text-xs text-slate-400 mt-1 block">Active across all projects</span>
          </div>
          <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <ListTodo className="w-6 h-6" />
          </div>
        </div>

        {/* In Progress */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">In Progress</span>
            <div className="text-2xl font-bold text-blue-600 mt-1">{stats?.status?.IN_PROGRESS ?? 0}</div>
            <span className="text-xs text-slate-400 mt-1 block">Currently being worked on</span>
          </div>
          <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <Clock className="w-6 h-6" />
          </div>
        </div>

        {/* Completed */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed</span>
            <div className="text-2xl font-bold text-emerald-600 mt-1">{stats?.status?.DONE ?? 0}</div>
            <span className="text-xs text-slate-400 mt-1 block">Fulfilled tasks</span>
          </div>
          <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <CheckCircle2 className="w-6 h-6" />
          </div>
        </div>

        {/* Overdue Count */}
        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-xs font-semibold text-red-600 uppercase tracking-wider">Overdue</span>
            <div className="text-2xl font-bold text-red-600 mt-1">{stats?.overdue_count ?? 0}</div>
            <span className="text-xs text-slate-400 mt-1 block">Urgent attention needed</span>
          </div>
          <div className="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
            <AlertTriangle className="w-6 h-6" />
          </div>
        </div>
      </div>

      {/* Main Grid: Overdue & Upcoming */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Overdue Tasks Section */}
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div className="p-5 border-b border-slate-100 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <AlertTriangle className="w-5 h-5 text-red-600" />
              <h2 className="text-base font-semibold text-slate-900">Overdue Deliverables</h2>
            </div>
            <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">
              {overdue_tasks?.length ?? 0} Critical
            </span>
          </div>
          <div className="divide-y divide-slate-100">
            {overdue_tasks && overdue_tasks.length > 0 ? (
              overdue_tasks.map((task) => (
                <div key={task.id} className="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between">
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-md border ${priorityBadgeColors[task.priority]}`}>
                        {task.priority}
                      </span>
                      <span className="text-xs text-slate-500 font-medium">{task.project.name}</span>
                    </div>
                    <h3 className="text-sm font-semibold text-slate-900">{task.title}</h3>
                    <p className="text-xs text-red-600 font-medium flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5" />
                      Due {task.due_at ? new Date(task.due_at).toLocaleDateString() : 'N/A'}
                    </p>
                  </div>
                  <Link
                    to={`/projects/${task.project.id}/board`}
                    className="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                  >
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </div>
              ))
            ) : (
              <div className="p-8 text-center text-slate-400 text-sm">
                No overdue tasks! You are completely on schedule.
              </div>
            )}
          </div>
        </div>

        {/* Upcoming Tasks (Next 7 Days) */}
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div className="p-5 border-b border-slate-100 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Calendar className="w-5 h-5 text-blue-600" />
              <h2 className="text-base font-semibold text-slate-900">Upcoming Deadlines (7 Days)</h2>
            </div>
            <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
              {upcoming_tasks?.length ?? 0} Pending
            </span>
          </div>
          <div className="divide-y divide-slate-100">
            {upcoming_tasks && upcoming_tasks.length > 0 ? (
              upcoming_tasks.map((task) => (
                <div key={task.id} className="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between">
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-md border ${priorityBadgeColors[task.priority]}`}>
                        {task.priority}
                      </span>
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-md ${statusBadgeColors[task.status]}`}>
                        {task.status}
                      </span>
                      <span className="text-xs text-slate-500 font-medium">{task.project.name}</span>
                    </div>
                    <h3 className="text-sm font-semibold text-slate-900">{task.title}</h3>
                    <p className="text-xs text-slate-500 flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5 text-slate-400" />
                      Due {task.due_at ? new Date(task.due_at).toLocaleDateString() : 'N/A'}
                    </p>
                  </div>
                  <Link
                    to={`/projects/${task.project.id}/board`}
                    className="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                  >
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </div>
              ))
            ) : (
              <div className="p-8 text-center text-slate-400 text-sm">
                No tasks scheduled for the next 7 days.
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Recent Activity Stream */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <div className="flex items-center gap-2 mb-4">
          <Activity className="w-5 h-5 text-slate-600" />
          <h2 className="text-base font-semibold text-slate-900">Recent Project Activity</h2>
        </div>
        <div className="space-y-3">
          {recent_activity && recent_activity.length > 0 ? (
            recent_activity.map((act) => (
              <div key={act.id} className="flex items-start gap-3 p-3 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                <div className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  {act.actor.name.charAt(0)}
                </div>
                <div className="flex-1">
                  <p className="text-slate-900">
                    <span className="font-semibold">{act.actor.name}</span>{' '}
                    <span className="text-slate-600">performed</span>{' '}
                    <span className="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[11px] text-blue-700 font-semibold">
                      {act.action}
                    </span>{' '}
                    <span className="text-slate-600">in</span>{' '}
                    <span className="font-semibold text-slate-800">{act.project.name}</span>
                  </p>
                  <span className="text-[10px] text-slate-400 mt-1 block">
                    {new Date(act.created_at).toLocaleString()}
                  </span>
                </div>
              </div>
            ))
          ) : (
            <p className="text-sm text-slate-400 text-center py-4">No recent activity recorded.</p>
          )}
        </div>
      </div>
    </div>
  );
};

