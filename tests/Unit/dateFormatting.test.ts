import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
  durationFrom,
  formatAbsoluteDate,
  formatRelativeDue,
} from '../../resources/js/Lib/dateFormatting.ts';

test('absolute formatting uses the configured timezone, including daylight saving', () => {
  const instant = '2026-07-01T00:00:00Z';
  assert.match(formatAbsoluteDate(instant, 'UTC'), /1 Jul 2026, 00:00 UTC/);
  assert.match(formatAbsoluteDate(instant, 'Asia/Jakarta'), /1 Jul 2026, 07:00/);
  assert.match(formatAbsoluteDate(instant, 'America/New_York'), /30 Jun 2026, 20:00/);
  assert.match(
    formatAbsoluteDate('2026-01-01T00:00:00Z', 'America/New_York'),
    /31 Dec 2025, 19:00/,
  );
  assert.equal(formatAbsoluteDate('invalid', 'UTC'), 'Date unavailable');
});

test('relative due dates and durations use an explicit reference and correct boundaries', () => {
  const reference = '2026-01-01T00:00:00Z';
  assert.equal(formatRelativeDue(reference, reference), 'Due now');
  assert.equal(formatRelativeDue('2026-01-01T02:00:00Z', reference), 'Due in 2 hours');
  assert.equal(formatRelativeDue('2025-12-31T23:00:00Z', reference), 'Due 1 hour ago');
  assert.equal(durationFrom('2025-12-30T00:00:00Z', reference), '2 days');
  assert.equal(durationFrom('2025-12-31T23:00:01Z', reference), '59 minutes');
  assert.equal(durationFrom('2025-12-31T23:59:01Z', reference), 'less than a minute');
  assert.equal(formatRelativeDue('invalid', reference), 'Due date unavailable');
  assert.equal(durationFrom(reference, 'invalid'), 'unknown duration');
});

test('overdue badge foreground and background meet WCAG AA normal-text contrast', () => {
  function luminance(hex: string) {
    const channels = [hex.slice(0, 2), hex.slice(2, 4), hex.slice(4, 6)].map((channel) => {
      const value = parseInt(channel, 16) / 255;
      return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });
    return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
  }
  assert.ok((luminance('fef2f2') + 0.05) / (luminance('b91c1c') + 0.05) >= 4.5);
});
