export type Role = 'USER' | 'ADMIN';
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
