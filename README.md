# ResolveIT

ResolveIT is an internal IT helpdesk and service desk ticketing system being developed for small organizations. It is designed to give employees a central place to report technical problems and request IT services, while helping support teams organize, prioritize and resolve those requests.

Instead of tracking support requests across inboxes, spreadsheets and chat conversations, ResolveIT brings them into a shared ticket queue. Each ticket is intended to have a clear status, an assigned owner, a resolution due date and a history of activity.

This is a full-stack portfolio project focused on practical support workflows, role-based access control and tested application code.

## What is ResolveIT used for?

- **Reporting IT issues:** Employees can submit problems such as a failed VPN connection, a software error or a malfunctioning device.
- **Requesting IT services:** Employees can request help with tasks such as software installation or account access.
- **Managing support work:** Support agents can triage requests, assign tickets and follow them through resolution.
- **Keeping context together:** Ticket comments, internal notes, attachments and activity history keep the information needed to solve an issue in one place.
- **Tracking deadlines:** Priority-based service-level agreement (SLA) due dates and overdue indicators help support teams identify work that needs attention.
- **Configuring the helpdesk:** Administrators can manage categories, priorities and user roles.

These workflows describe the planned MVP. See the development status below for what is currently available.

## Who is it for?

| Role          | Intended use                                                                                                           |
| ------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Employee      | Submit tickets, follow their progress, reply to support agents and confirm a resolution or reopen an unresolved issue. |
| Agent         | Review the support queue, take ownership of tickets, communicate with employees and resolve issues.                    |
| Administrator | Perform support work and manage helpdesk categories, priorities and user roles.                                        |

## Planned ticket workflow

```text
Open → Assigned → In Progress → Resolved → Closed
```

An employee submits a ticket with a title, description, category and priority. A support agent takes ownership, investigates the issue and records the resolution. The employee can then close the ticket or reopen it if the issue persists.

The MVP also plans search and filters, private attachments, internal notes, an activity timeline, assignment and resolution email notifications, and an operational dashboard. ResolveIT is scoped as a single-organization internal application.
