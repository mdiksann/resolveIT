export type Role = 'EMPLOYEE' | 'AGENT' | 'ADMIN';
export type TicketStatus = 'OPEN' | 'ASSIGNED' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED';
export type TicketEvent =
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
