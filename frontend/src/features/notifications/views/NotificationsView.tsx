import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { notificationsService } from '../api/notificationsService';
import { AppNotification } from '../types/notifications.types';
import { Bell, CheckCheck, Clock, CheckCircle } from 'lucide-react';
import { Link } from 'react-router-dom';

export const NotificationsView: React.FC = () => {
  const queryClient = useQueryClient();
  const [unreadOnly, setUnreadOnly] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['notifications', unreadOnly],
    queryFn: () => notificationsService.listNotifications(unreadOnly),
  });

  const markReadMutation = useMutation({
    mutationFn: (id: string) => notificationsService.markAsRead(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] });
    },
  });

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Notification Center</h1>
          <p className="text-sm text-slate-500 mt-1">
            Real-time notifications about assignments, status changes, and team invites.
          </p>
        </div>

        <button
          onClick={() => setUnreadOnly(!unreadOnly)}
          className={`text-xs font-semibold px-3 py-1.5 rounded-xl border transition-colors ${
            unreadOnly
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
          }`}
        >
          {unreadOnly ? 'Showing Unread' : 'Filter Unread'}
        </button>
      </div>

      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-20 bg-slate-200 rounded-2xl" />
          ))}
        </div>
      ) : data?.data && data.data.length > 0 ? (
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
          {data.data.map((n: AppNotification) => (
            <div
              key={n.id}
              className={`p-4 flex items-start justify-between gap-4 transition-colors ${
                n.read_at ? 'bg-white' : 'bg-blue-50/30'
              }`}
            >
              <div className="flex items-start gap-3">
                <div
                  className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${
                    n.read_at ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-600'
                  }`}
                >
                  <Bell className="w-4 h-4" />
                </div>
                <div>
                  <p className="text-xs font-bold text-slate-900">
                    {n.data.message || 'System Notification'}
                  </p>
                  {n.data.project_name && (
                    <p className="text-[11px] text-slate-500 mt-0.5">
                      Project: <span className="font-semibold">{n.data.project_name}</span>
                    </p>
                  )}
                  <span className="text-[10px] text-slate-400 mt-1 block">
                    {new Date(n.created_at).toLocaleString()}
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-2">
                {!n.read_at && (
                  <button
                    onClick={() => markReadMutation.mutate(n.id)}
                    className="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 text-xs font-semibold flex items-center gap-1"
                    title="Mark as read"
                  >
                    <CheckCheck className="w-4 h-4" />
                    <span className="hidden sm:inline">Mark read</span>
                  </button>
                )}
                {n.data.project_id && (
                  <Link
                    to={`/projects/${n.data.project_id}/board`}
                    className="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold"
                  >
                    View Board
                  </Link>
                )}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="p-12 text-center bg-white rounded-2xl border border-slate-200">
          <CheckCircle className="w-10 h-10 text-emerald-400 mx-auto mb-2" />
          <h3 className="text-sm font-semibold text-slate-800">All caught up!</h3>
          <p className="text-xs text-slate-400 mt-1">You have no new notifications.</p>
        </div>
      )}
    </div>
  );
};

