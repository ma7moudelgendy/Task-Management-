import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';
import { AppLayout } from '@/layouts/AppLayout';
import { ProtectedRoute, GuestRoute } from './RouteGuards';
import { LoginView } from '@/features/auth/views/LoginView';
import { RegisterView } from '@/features/auth/views/RegisterView';
import { DashboardView } from '@/features/dashboard/views/DashboardView';
import { TeamsView } from '@/features/teams/views/TeamsView';
import { ProjectsView } from '@/features/projects/views/ProjectsView';
import { ProjectDetailView } from '@/features/projects/views/ProjectDetailView';
import { NotificationsView } from '@/features/notifications/views/NotificationsView';

export const AppRoutes: React.FC = () => {
  return (
    <Routes>
      {/* Public Guest Routes */}
      <Route element={<GuestRoute />}>
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<LoginView />} />
          <Route path="/register" element={<RegisterView />} />
        </Route>
      </Route>

      {/* Authenticated Protected Routes */}
      <Route element={<ProtectedRoute />}>
        <Route element={<AppLayout />}>
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={<DashboardView />} />
          <Route path="/teams" element={<TeamsView />} />
          <Route path="/projects" element={<ProjectsView />} />
          <Route path="/projects/:id" element={<ProjectDetailView />} />
          <Route path="/projects/:id/board" element={<ProjectDetailView />} />
          <Route path="/projects/:id/analytics" element={<ProjectDetailView />} />
          <Route path="/notifications" element={<NotificationsView />} />
        </Route>
      </Route>

      {/* Fallback */}
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
};

