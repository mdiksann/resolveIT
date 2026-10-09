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
  email: string;
  role: Role;
}
export interface SharedProps {
  [key: string]: unknown;
  appName: string;
  auth: { user: User | null; canAccessAdmin: boolean };
  flash: Partial<Record<'success' | 'error' | 'warning' | 'info', string>>;
}
