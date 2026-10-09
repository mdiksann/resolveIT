import { useEffect, useRef } from 'react';
export function FormErrors({
  errors,
  failure,
  fieldIds,
}: {
  errors: Partial<Record<string, string>>;
  failure?: string;
  fieldIds?: Record<string, string>;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const entries = Object.entries(errors).filter((entry): entry is [string, string] => !!entry[1]);
  useEffect(() => {
    if (Object.keys(errors).length) ref.current?.focus();
  }, [errors]);
  if (!entries.length && !failure) return null;
  return (
    <div
      ref={ref}
      tabIndex={-1}
      role="alert"
      className="rounded-control border border-destructive p-4 text-sm text-destructive"
    >
      <p className="font-semibold">{failure || 'The change was not saved. Check these fields:'}</p>
      <ul>
        {entries.map(([field, error]) => (
          <li key={field}>
            <a className="underline" href={`#${fieldIds?.[field] ?? field}`}>
              {error}
            </a>
          </li>
        ))}
      </ul>
    </div>
  );
}
