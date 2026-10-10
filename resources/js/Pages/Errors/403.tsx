import { ErrorLayout } from './ErrorLayout';

export default function Forbidden() {
  return (
    <ErrorLayout
      status={403}
      title="Access denied"
      description="You do not have permission to access this resource or perform this action."
    />
  );
}

