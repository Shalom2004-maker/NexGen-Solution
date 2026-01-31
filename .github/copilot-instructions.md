# NexGen Solution - Copilot Instructions

## Architecture Overview

**NexGen Solution** is a **multi-role employee management system** built with vanilla PHP/MySQL. The codebase follows a **role-based access control (RBAC)** pattern with four user roles: Admin, HR, ProjectLeader, and Employee.

### Key Components

- **`public/`** - Public-facing pages: login, homepage, contact (no auth required)
- **`dashboard/`** - Role-specific management interfaces (all require authentication)
- **`includes/`** - Shared PHP utilities: database connection, authentication, logging
- **Database** - MySQL (`nexgen_solutions`); schema in [includes/nexgen_solutions.sql](includes/nexgen_solutions.sql)

### Role Hierarchy & Permissions

```
Admin          → Full access to all dashboards
HR             → Manage employees, inquiries, leave, payroll
ProjectLeader  → View tasks, leave, payroll for teams
Employee       → View own leave requests, profile, projects
```

Use [includes/auth.php](includes/auth.php) `allow()` function to enforce permissions:

- `allow('Admin')` - Single role check
- `allow(['HR', 'Admin'])` - Multiple roles (case-insensitive)
- Admin always bypasses checks

## Database Schema Patterns

Core tables: `users` (auth), `employees` (details), `roles`, `leave_requests`, `tasks`, `inquiries`

**Key foreign key relationship**: `users.id` → `employees.user_id` (one employee per user)

When fetching employee data, always JOIN through this relationship:

```sql
SELECT l.*, e.user_id, u.full_name AS employee_name
FROM leave_requests l
JOIN employees e ON l.employee_id = e.id
JOIN users u ON e.user_id = u.id
```

## Common Workflows & Patterns

### CRUD Operations

Dashboard follows a consistent **4-file pattern** for each module (e.g., leave, tasks, inquiries):

- **`leave_view.php`** - List with search, filters, pagination (limit=10), role-based row filtering
- **`leave_edit.php`** - Display form for editing/viewing details
- **`leave_update.php`** - Process POST requests (create, update, delete)
- **`leave_delete.php`** - Standalone delete handler

See [dashboard/leave_view.php](dashboard/leave_view.php#L15-L75) for comprehensive pagination & parameterized query example.

### Parameterized Queries (Always Use)

```php
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = ? AND status = ?");
$stmt->bind_param('is', $empId, $status);  // 'i'=int, 's'=string
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
```

**Never use string concatenation for user input** - SQL injection risk.

### Session & CSRF Protection

- Session checks in [includes/auth.php](includes/auth.php) - verify `$_SESSION["uid"]` and `$_SESSION["role"]`
- CSRF tokens generated/validated in edit/update flows:
  ```php
  if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
  }
  ```

### Role-Based Row Filtering

Employees see **only their own data** in views. Example from leave_view.php:

```php
if ($role === 'Employee') {
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
    $empStmt->bind_param('i', $uid);
    $empStmt->execute();
    if ($empRow = $empStmt->get_result()->fetch_assoc()) {
        $where = 'WHERE l.employee_id = ?';
        $params[] = $empRow['id'];
    }
}
```

## Critical Files

| File                                       | Purpose                                                        |
| ------------------------------------------ | -------------------------------------------------------------- |
| [includes/db.php](includes/db.php)         | MySQLi connection setup (localhost, root, nexgen_solutions DB) |
| [includes/auth.php](includes/auth.php)     | Session validation, role-based access control                  |
| [public/login.php](public/login.php)       | Authentication entry point; sets role in session               |
| [includes/logger.php](includes/logger.php) | Audit logging with IP tracking                                 |

## Code Style & Conventions

- **Security**: Always use prepared statements; sanitize output with `htmlspecialchars()`
- **Session vars**: `$_SESSION['uid']` (user ID), `$_SESSION['role']` (role name), `$_SESSION['csrf_token']`
- **Error handling**: Use `header()` redirects and HTTP response codes (403 for forbidden)
- **UI**: Bootstrap 5.3 (CDN + local fallback), Bootstrap Icons for glyphs
- **Comments**: Focus on "why" not "what" - e.g., explain permission logic

## Debugging & Logging

Use `audit_log()` from [includes/logger.php](includes/logger.php) to record actions:

```php
require_once __DIR__ . "/../includes/logger.php";
audit_log('update_leave', "Changed leave status from $old to $new", $uid);
```

Logs stored in `logs/audit.log` with timestamp, IP, user ID, action, message.

## Dashboard Module Patterns

### Leave Management (`leave_*.php`)

- **Workflow**: Multiple approval stages (pending → leader_approved → hr_approved → rejected)
- **Permissions**: Employees can only edit/view own pending requests; HR/Leaders can approve/reject
- **Key fields**: `start_date`, `end_date`, `leave_type` (enum: sick|annual|unpaid), `reason`, `status`
- **Example**: [dashboard/leave_update.php](dashboard/leave_update.php#L58-L78) enforces role-based status changes

### Inquiries Management (`inquiries_*.php`)

- **Restrictions**: HR role only (`allow("HR")` in view)
- **Search**: Multi-field search across `name`, `email`, `company`, `message` (all LIKE queries)
- **Status workflow**: `new` → `replied` → `closed`
- **Pattern**: Uses LIKE with wildcard binding `"%{$q}%"` for text search
- **Example**: [dashboard/inquiries_view.php](dashboard/inquiries_view.php#L23-L36) demonstrates multi-field search

### Tasks Management (`tasks_*.php`)

- **Role-based visibility**: ProjectLeader/Admin see all tasks; others see only assigned tasks
- **Filtering logic**: Non-leaders filtered by `WHERE t.assigned_to = ?` (user ID)
- **Joins**: Task view requires 3 LEFT JOINs (assigned user, created user, project)
- **Key fields**: `title`, `description`, `status`, `deadline`, `assigned_to`, `project_id`
- **Example**: [dashboard/tasks_view.php](dashboard/tasks_view.php#L26-L40) shows conditional WHERE for role-based filtering

### Common Search Patterns (All Modules)

```php
// Multi-field LIKE search with parameterized queries
if ($q !== '') {
    $like = "%{$q}%";
    $where = 'WHERE (field1 LIKE ? OR field2 LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Status filter (always AND with previous conditions)
if ($statusFilter !== '') {
    if ($where === '') {
        $where = 'WHERE status = ?';
    } else {
        $where .= ' AND status = ?';
    }
    $params[] = $statusFilter;
    $types .= 's';
}
```

## Deployment & Migration Workflows

### Database Migration Script

Use [scripts/migrate_db.php](scripts/migrate_db.php) for schema initialization:

```bash
php scripts/migrate_db.php
```

**What it does**:

1. Creates `roles` table if missing
2. Seeds default roles: Employee, ProjectLeader, HR, Admin
3. Adds `role_id` column to `users` table (idempotent)
4. Cleans invalid foreign key references
5. Adds FK constraint `users.role_id` → `roles.id`
6. Uses transactions (`autocommit(false)`) to roll back on error

**Key pattern**: All operations are **idempotent** - safe to run multiple times

### Fresh Deployment

1. Create MySQL database: `CREATE DATABASE nexgen_solutions;`
2. Import schema: `mysql nexgen_solutions < includes/nexgen_solutions.sql`
3. Run migration: `php scripts/migrate_db.php`
4. Configure web root to project folder in Apache
5. Access via `http://localhost/` (or XAMPP alias)

### Role Assignment

Roles are assigned during login and stored in session:

- Login queries `users` JOIN `roles` to fetch `role_name`
- Session stores `$_SESSION['role']` for access control throughout dashboard
- Default roles: Admin, HR, ProjectLeader, Employee
- **Never modify** `$_SESSION['role']` client-side; only set on server during auth

### Testing Across Roles

Dashboard redirects to role-specific entry points after login:

- **Admin**: `dashboard/admin_dashboard.php`
- **HR**: `dashboard/hr.php`
- **ProjectLeader**: `dashboard/leader.php`
- **Employee**: `dashboard/employee.php`

Test permission boundaries by:

1. Logging in as different roles
2. Attempting cross-role API access (should get 403)
3. Checking logs at `logs/audit.log` for blocked access attempts

## Sidebar & Navigation Structure

### Admin Sidebar ([dashboard/admin_siderbar.php](dashboard/admin_siderbar.php))

The **Admin sidebar is unified** - it contains menu items for all roles. Each section is labeled with headings and includes role-appropriate links:

**Employee Section**:

- Dashboard (`admin_dashboard.php`)
- My Tasks (`tasks.php`)
- Request Leave (`leave.php`)
- My Salary (`salary.php`)

**Project Leader Section**:

- Overview (`leader.php`)
- Tasks Assignment (`tasks.php`)
- Leave Review (`leave_view.php`)

**HR Section**:

- Employees (`hr.php`)
- Leave Approvals (`leave_view.php`)
- Process Payroll (`leader_payroll.php`)
- Inquiries (`inquiries_view.php`)

**Admin Section**:

- System Users (`admin_user.php`)

**Key features**:

- Active page detection via `basename($_SERVER['PHP_SELF'])` - adds `.active` class with blue highlight
- Bootstrap Icons for menu items
- Responsive: Fixed on desktop, toggle drawer on mobile (<768px)
- Footer displays user avatar, name, role, and logout button
- Menu auto-closes after click on mobile devices

### Role-Specific Entry Points

Each role has a dedicated dashboard entry file that:

1. Calls `allow('RoleName')` at top to enforce access
2. Includes `admin_siderbar.php` for unified navigation
3. Displays role-appropriate content/metrics

| Role          | Dashboard File                                                 | Page Title                                                       |
| ------------- | -------------------------------------------------------------- | ---------------------------------------------------------------- |
| Admin         | [dashboard/admin_dashboard.php](dashboard/admin_dashboard.php) | Shows metrics: employees, active tasks, pending leaves, projects |
| HR            | [dashboard/hr.php](dashboard/hr.php)                           | HR management hub                                                |
| ProjectLeader | [dashboard/leader.php](dashboard/leader.php)                   | Project leader overview                                          |
| Employee      | [dashboard/employee.php](dashboard/employee.php)               | Personal employee dashboard                                      |

### Navigation Pattern

All dashboard pages use this include structure:

```php
<?php
include "../includes/auth.php";
allow("RoleName");  // Enforce role at page top
include "../includes/db.php";
?>
<!-- Sidebar included via admin_siderbar.php in HTML body -->
<!-- Main content uses flex layout: sidebar (fixed) + main-content (flex-1) -->
```

The sidebar is styled as **fixed position** sidebar with `.main-wrapper` using flexbox:

- `.nexgen-sidebar` (fixed, ~250px width on desktop)
- `.main-content` (flex-1, grows to fill remaining space)

### Active Page Highlighting

Sidebar menu items detect current page dynamically:

```php
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<a href="tasks.php" class="<?= $current_page === 'tasks.php' ? 'active' : '' ?>">
```

Active link styles:

- Background: `#337ccfe2` (blue)
- Color: `white`
- Font-weight: `600`

## Development Environment

- **Server**: XAMPP (Apache + MySQL 10.4.32)
- **PHP**: 8.1.25+
- **Database location**: `localhost` / root user / `nexgen_solutions` database
- **Import schema**: `includes/nexgen_solutions.sql` via phpMyAdmin or `mysql nexgen_solutions < nexgen_solutions.sql`
- **Run migrations**: `php scripts/migrate_db.php` from project root

---

**When modifying dashboard files**: Start from `_view.php`, update `_update.php` for business logic, ensure role checks in all files, test with different roles. All searches use parameterized LIKE queries with wildcard prefixes. Always include `allow('RoleName')` check immediately after auth include in role-specific pages.
