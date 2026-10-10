import { ErrorLayout } from './ErrorLayout';

export default function NotFound() {
  return (
    <ErrorLayout
      status={404}
      title="Page not found"
      description="The page or resource you are looking for does not exist or has been moved."
    />
  );
}
