import { UserRole } from './enums';

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  is_active: boolean;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

