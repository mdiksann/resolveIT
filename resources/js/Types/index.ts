export type Role = 'EMPLOYEE' | 'AGENT' | 'ADMIN';
export type TicketStatus = 'OPEN' | 'ASSIGNED' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED';
export type TicketEvent =
  | 'updated'
  | 'created'
  | 'status_changed'
  | 'assigned'
  | 'unassigned'
  | 'priority_changed'
  | 'category_changed'
  | 'commented'
  | 'attachment_added'
  | 'attachment_removed';
export interface User {
  id: number;
  name: string;
  role: Role;
}
export interface SharedProps {
  [key: string]: unknown;
  appName: string;
  auth: {
    user: User | null;
    canAccessAdmin: boolean;
    can: {
      viewAnyTicket: boolean;
      manageCategories: boolean;
      managePriorities: boolean;
      manageUsers: boolean;
    };
  };
  flash: Partial<Record<'success' | 'error' | 'warning' | 'info', string>>;
}

export interface AdminUser extends User {
  email: string;
  requested_tickets_count: number;
  assigned_tickets_count: number;
}
export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  from: number | null;
  to: number | null;
  total: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}
export interface RoleOption {
  value: Role;
  label: string;
}

export interface CategoryOption {
  id: number;
  name: string;
}
export interface PriorityOption extends CategoryOption {
  rank: number;
  is_default?: boolean;
}
export interface TicketFields {
  title: string;
  description: string;
  category_id: string;
  priority_id: string;
}

export interface TicketSummary {
  id: number;
  number: string;
  title: string;
  status: TicketStatus;
  category_id: number | null;
  priority_id: number;
  requester: CategoryOption | null;
  assignee: CategoryOption | null;
  category: CategoryOption | null;
  priority: PriorityOption;
  created_at: string;
  due_at: string;
  overdue: boolean;
}
export interface StatusOption {
  value: TicketStatus;
  label: string;
}
export interface TicketFilters {
  search?: string;
  status?: TicketStatus | '';
  priority?: string;
  category?: string;
  assignee?: string;
  mine?: boolean | number;
  overdue?: boolean | number;
  sort?: string;
}

export interface TicketDetail extends TicketSummary {
  description: string;
  resolved_at: string | null;
  closed_at: string | null;
}
export interface TicketCapabilities {
  attach: boolean;
  comment: boolean;
  internal: boolean;
  update: boolean;
  assign: boolean;
}

export interface TicketComment {
  id: number;
  body: string;
  is_internal: boolean;
  created_at: string;
  author: CategoryOption | null;
}

export interface TicketAttachment {
  id: number;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  created_at: string;
  uploader: CategoryOption | null;
  can_delete: boolean;
  download_url: string;
}

export interface TicketActivity {
  id: number;
  event: TicketEvent | string;
  summary: string;
  actor: CategoryOption | null;
  created_at: string;
  relative_time: string;
}
