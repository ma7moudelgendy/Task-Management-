export type ApiOutcomeKey =
  | 'success'
  | 'fail'
  | 'unauthenticated'
  | 'unauthorized'
  | 'not_found'
  | 'blocked'
  | 'needActive'
  | 'exception';

export interface TaskFlowEnvelope<T = unknown> {
  key: ApiOutcomeKey;
  code: number;
  msg: string;
  data: T;
  response_status: {
    error: boolean;
    validation_errors?: Record<string, string[]>;
  };
}

export interface PaginationMeta {
  current_page: number;
  last_page?: number;
  per_page?: number;
  total: number;
}

export interface PaginatedData<T> {
  data: T[];
  meta: PaginationMeta;
  links?: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}

export interface TaskHelpers {
  can_update: boolean;
  can_delete: boolean;
  can_assign: boolean;
}

export interface TeamHelpers {
  can_update: boolean;
  can_delete: boolean;
  can_manage_members: boolean;
  can_change_role: boolean;
}

export interface ProjectHelpers {
  can_update: boolean;
  can_delete: boolean;
  can_manage_members: boolean;
  can_change_role: boolean;
}

export interface CommentHelpers {
  can_update: boolean;
  can_delete: boolean;
}

