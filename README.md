# Support Ticket & SLA Management System

A production-grade, enterprise-ready **Support Ticket & SLA Management System** built with **Laravel 12**, **Tailwind CSS v4**, **Alpine.js**, and **TailAdmin**.

This application enables customers to submit and track support requests, and empowers support agents and administrators to manage, claim, assign, escalate, and resolve tickets within business-hour-aware SLA deadlines.

---

## 🚀 Key Highlights & Business Capabilities

1. **Sequential & Scalable Ticket ID Generation:**
   - Format: `TKT-YYYY-000001` (e.g. `TKT-2026-000001`).
   - Concurrency-safe sequence generation with database locking to prevent sequence collisions.

2. **Accurate Business Hours SLA Calculation (`SlaCalculatorService`):**
   - Configurable resolution time per priority:
     - **Critical:** 2 Hours
     - **High:** 8 Hours
     - **Medium:** 24 Hours
     - **Low:** 48 Hours
   - **Business Days:** Sunday – Thursday (configurable in `config/sla.php`).
   - **Business Hours:** 9:00 AM – 6:00 PM.
   - Non-working hours, weekends, and holidays are mathematically skipped. (e.g. A *Critical* 2-hour ticket created on Thursday at 5:00 PM has an SLA deadline of **Sunday at 10:00 AM**).

3. **Race-Condition-Safe Concurrent Ticket Assignment (`TicketAssignmentService`):**
   - Implements **Database Pessimistic Locking (`lockForUpdate()`)** within transactions.
   - Prevents two agents from claiming or accepting the same unassigned ticket simultaneously.
   - **Automatic Workload Balancing:** Auto-assigns tickets to the support agent with the fewest active tickets.

4. **Role-Based Server-Side Authorization (RBAC):**
   - Roles: `admin`, `agent`, `customer`.
   - **Customer Isolation:** Customers only access their own tickets.
   - **Private Internal Notes:** Support agents and administrators can post internal notes (`is_internal = true`) with gold visual highlights. **Internal notes and their attachments are strictly hidden from customers at both UI and server policy levels.**

5. **Secure File Storage & DRY File Helper (`FileHelper`):**
   - File validation (MIME types, maximum size limits).
   - DRY helper functions for upload, update, and automatic deletion of old files on update/delete.
   - Protected download endpoints requiring view policy authorization.

6. **Automated SLA Escalation & Scheduled Tasks:**
   - Artisan command: `php artisan ticket:check-sla`
   - Automatically marks breached tickets as escalated, logs audit history, and notifies agents and administrators.
   - Alerts agents on tickets approaching breach (< 2 hours remaining).

7. **Asynchronous Queued Notifications:**
   - Notifications for ticket creation, agent assignment, customer/agent replies, and SLA escalations.
   - Queued via `ShouldQueue` on the `database` driver for non-blocking HTTP requests.

8. **Memory-Efficient Streaming CSV Import:**
   - Streamed processing (`fgetcsv`) maintaining $O(1)$ memory usage for files of any size.
   - Validates required headers and rows, detects duplicate submissions, and executes chunked database transactions.
   - Downloadable CSV template and complete error reporting with line numbers.

9. **Interactive Dashboard & Audit Timeline:**
   - KPI metrics: Open, Unassigned, Critical, SLA Breached, Approaching Breach, and Resolved.
   - Workload distribution by support agent.
   - Chronological audit history log for all ticket events (creation, assignment, replies, escalations).

---

## 🛠️ Tech Stack

- **Backend:** Laravel 12.x, PHP >= 8.2
- **Database:** MySQL 8.x / SQLite
- **Frontend / Styling:** Tailwind CSS v4, Alpine.js v3, Vite 7
- **Admin Layout:** TailAdmin Free Laravel Edition
- **Testing:** Pest PHP 4.x / PHPUnit 12.x

---

## 💻 Local Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/Jalismahamud/ticket-management.git
cd ticket-management
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
Copy `.env.example` to `.env` (or configure your database details in `.env`):
```bash
cp .env.example .env
php artisan key:generate
```

Verify your database settings in `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_manager
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

### 4. Run Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

### 5. Build Frontend Assets
```bash
npm run build
# Or for development with hot-reload:
# npm run dev
```

### 6. Start the Development Server
```bash
php artisan serve
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🔑 Pre-Seeded Demo Accounts

All accounts share the default password: `password`

| Role | Email | Password | Access Level |
|---|---|---|---|
| **Administrator** | `admin@example.com` | `password` | Full system access, reassignments, all tickets & SLA metrics |
| **Support Agent 1** | `agent1@example.com` | `password` | Technical Support, ticket claiming, internal notes |
| **Support Agent 2** | `agent2@example.com` | `password` | Billing & Accounts, ticket claiming, internal notes |
| **Customer 1** | `customer1@example.com` | `password` | Permitted access to own tickets only |
| **Customer 2** | `customer2@example.com` | `password` | Permitted access to own tickets only |

> 💡 **Quick Switcher:** On the Sign-In page and Dashboard, a 1-click **Role Switcher** is provided for instant evaluator testing without manual credential re-typing.

---

## ⚙️ Background Processing & Scheduler

### 1. Queue Worker
To process background notifications and emails without blocking HTTP requests:
```bash
php artisan queue:work
```

### 2. SLA Escalation Scheduler
To run SLA monitoring manually:
```bash
php artisan ticket:check-sla
```
To run the Laravel Scheduler daemon locally:
```bash
php artisan schedule:work
```

---

## 🧪 Automated Testing

The project includes automated feature tests covering core business requirements:
- **SLA Calculation:** Thursday 5:00 PM + 2h Critical SLA -> Sunday 10:00 AM, multi-day roll-overs, and weekend detection.
- **Concurrent Assignment:** Verifies race condition prevention using `lockForUpdate()` and checks `TicketAlreadyAssignedException`.
- **Authorization:** Customer isolation, 403 barriers, and complete concealment of internal agent notes from customers.
- **Ticket Lifecycle & CSV Import:** Sequential ID generation, status transitions, and duplicate prevention.

To run the complete test suite:
```bash
php artisan test
```

---

## 📐 Architecture & Key Design Decisions

### 1. SLA Business Hours Calculation (`App\Services\SlaCalculatorService`)
- Defined in `config/sla.php`.
- Algorithm snaps timestamps outside working windows to 9:00 AM of the next valid business day.
- Iteratively accumulates minutes across business days while respecting daily closing hours (6:00 PM).

### 2. Concurrency Safety (`App\Services\TicketAssignmentService`)
- When an agent claims an unassigned ticket, a database pessimistic lock (`Ticket::where('id', $id)->lockForUpdate()->first()`) is acquired within a `DB::transaction()`.
- If another agent submitted a claim within milliseconds, the lock ensures serialized execution: the second agent encounters a non-null `assigned_to` and receives a user-friendly `TicketAlreadyAssignedException`.

### 3. Complete Separation of Internal Notes
- Internal notes are stored in `ticket_replies` with `is_internal = true`.
- In `TicketController@show`, if `auth()->user()->isCustomer()`, only public replies are queried (`->where('is_internal', false)`).
- `TicketPolicy::addInternalNote` enforces server-side permission checks.
- Attachments associated with internal notes reject customer download attempts with HTTP 403.

### 4. Database Indexing Strategy
Compound and selective indexes are configured in migrations:
- `['status', 'priority']`
- `['assigned_to', 'status']`
- `['created_by', 'status']`
- `['status', 'sla_due_at']`
- `['is_escalated', 'sla_due_at']`
- `['ticket_id', 'is_internal', 'created_at']`
These indexes guarantee query response times remain optimal as ticket and conversation counts scale into hundreds of thousands of rows.
