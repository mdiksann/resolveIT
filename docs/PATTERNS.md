# Framework patterns for future projects

Examples are documentation recipes, not installed domain features.

## Pagination, search and filters

Validate query input in a Form Request: optional search string with a maximum length, enum/allowlist filters, positive page number and bounded per-page choices. Authorize the resource, scope its query to the actor, eager-load actual relations and use a stable sort.

```php
$filters = $request->validated();
// $query is an authorized, resource-specific Eloquent Builder.
$page = $query
    ->when($filters['search'] ?? null, fn ($query, $search) =>
        $query->where('name', 'ilike', '%'.$search.'%'))
    ->orderBy('id')
    ->paginate(20)
    ->withQueryString();
```

Bindings prevent SQL injection; `%` and `_` remain wildcard characters unless a product deliberately escapes them. Cap query length and choose an indexed search strategy when data volume requires it. Use allowlists for sort columns; bindings do not make arbitrary column names safe.

Return filters and pagination props to an Inertia page. Update filters using `router.get()` with `preserveState`, `preserveScroll` and `replace`; omit/reset `page` when filters change. Pagination links use Inertia `Link`. No universal search abstraction is included.

## Transactions

A generic atomic User update and database-session revocation:

```php
DB::transaction(function () use ($user, $validatedName) {
    $user->update(['name' => $validatedName]);
    DB::table('sessions')->where('user_id', $user->id)->delete();
});
```

This is an example of related writes only, not the starter's profile behavior. Authorize first. Dispatch notifications/events after commit when they depend on committed state. Consider concurrent updates and retries before adding transaction complexity.

## Notifications

Laravel's User already uses `Notifiable`. Create project-specific notifications with `php artisan make:notification ...`, return `['mail']` from `via()`, and implement `toMail()`. Configure a real mail transport in the target environment. The starter's log mailer supports local password-reset inspection without an additional service; reset links in logs must remain private.

Database notifications are optional. When a product needs them, run `php artisan make:notifications-table`, migrate, add `database` to notification channels, implement `toDatabase()` and authorize notification retrieval/mark-as-read. No notification table is installed by default.

## Queues and events

The default queue is `database`. Laravel's jobs, job batches and failed jobs tables are infrastructure. Use `ShouldQueue` for real asynchronous work, explicit retry/backoff/timeout policies, idempotent handlers, and `afterCommit()` when dispatching from transactions. Do not store credentials or unnecessary sensitive payloads in jobs.

```bash
php artisan queue:work --tries=3 --timeout=90
php artisan queue:failed
php artisan queue:retry <failed-job-id>
php artisan queue:restart
```

Keep worker timeout below `retry_after` to avoid duplicate execution. Production workers need supervision and graceful deployment restarts. Move to Redis by configuring a supported Redis client/connection, changing `QUEUE_CONNECTION=redis`, provisioning the service and retesting retry/failure behavior. Redis is not required here.

Use Laravel events/listeners for decoupled side effects, notifications, audit hooks or asynchronous workflows. Prefer queued listeners after commit where appropriate. Simple CRUD does not need an event bus or event-sourced architecture. No fake job, event or listener is installed.

## File uploads

Authorize uploads and validate with a Form Request. Use Laravel file validation with an explicit MIME/type allowlist and size ceiling, for example `File::types(['pdf'])->max('5mb')`. Content-based MIME checks are useful but do not guarantee a document is safe; add scanning for a product's risk level. Do not trust original names, extensions or client MIME headers.

```php
$path = $request->file('document')->store('documents', 'local');
```

Laravel generates a name. Store a relative path, not an arbitrary client-supplied path. The local disk is private by default. Serve private files through an authorized controller using `Storage::disk('local')->download($path)`; never expose those paths via a public storage link. Use the `public` disk and `php artisan storage:link` only for intentionally public files. Configure PHP/web-server request size limits alongside validation. Do not serve uploaded HTML/SVG as trusted application markup.

When storage writes and database writes interact, plan compensation for failures; database transactions cannot roll back external file writes. Add cloud storage only when a product requires it, through Laravel's Storage abstraction.
