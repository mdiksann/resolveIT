export function formatAbsoluteDate(value: string, timeZone: string): string {
  const date = new Date(value);
  if (!Number.isFinite(date.getTime())) return 'Date unavailable';
  return new Intl.DateTimeFormat('en-GB', {
    timeZone,
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZoneName: 'short',
  }).format(date);
}

// Use the server's response timestamp so display never depends on the browser clock.
export function durationFrom(value: string, referenceTime: string): string {
  const delta = Math.abs(Date.parse(value) - Date.parse(referenceTime));
  if (!Number.isFinite(delta)) return 'unknown duration';
  for (const [unit, size] of [
    ['day', 86400000],
    ['hour', 3600000],
    ['minute', 60000],
  ] as const) {
    if (delta >= size) {
      const count = Math.floor(delta / size);
      return `${count} ${unit}${count === 1 ? '' : 's'}`;
    }
  }
  return 'less than a minute';
}

export function formatRelativeDue(value: string, referenceTime: string): string {
  const delta = Date.parse(value) - Date.parse(referenceTime);
  if (!Number.isFinite(delta)) return 'Due date unavailable';
  if (delta === 0) return 'Due now';
  const duration = durationFrom(value, referenceTime);
  return delta > 0 ? `Due in ${duration}` : `Due ${duration} ago`;
}
