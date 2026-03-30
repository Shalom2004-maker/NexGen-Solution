<?php
include "../includes/auth.php";
allow("Admin");
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";
include "../includes/sidebar_helper.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int) ($_SESSION['uid'] ?? 0);
$entity = strtolower(trim((string) ($_GET['entity'] ?? 'categories')));
$entityMeta = [
    'categories' => ['title' => 'Categories Management', 'subtitle' => 'Organize the public catalog with reusable content categories.', 'singular' => 'Category', 'plural' => 'Categories', 'icon' => 'bi-tags', 'table' => 'categories', 'pk' => 'CategoryID'],
    'services' => ['title' => 'Services Management', 'subtitle' => 'Create, update, and refine services that appear on the public site.', 'singular' => 'Service', 'plural' => 'Services', 'icon' => 'bi-briefcase', 'table' => 'services', 'pk' => 'ServiceID'],
    'solutions' => ['title' => 'Solutions Management', 'subtitle' => 'Manage solution cards, publishing status, and category mapping.', 'singular' => 'Solution', 'plural' => 'Solutions', 'icon' => 'bi-lightbulb', 'table' => 'solutions', 'pk' => 'SolutionID'],
    'support' => ['title' => 'Support Management', 'subtitle' => 'Track support records and link them to services and solutions.', 'singular' => 'Support Record', 'plural' => 'Support Records', 'icon' => 'bi-life-preserver', 'table' => 'support', 'pk' => 'ID'],
];

if (!isset($entityMeta[$entity])) {
    $entity = 'categories';
}

$meta = $entityMeta[$entity];
$statusOptions = ['Open', 'In Progress', 'Resolved', 'Closed'];

function manager_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function manager_url(string $entity, array $extra = []): string
{
    return 'content_manager.php?' . http_build_query(array_merge(['entity' => $entity], $extra));
}

function manager_fetch_pairs(mysqli $conn, string $sql, string $idKey, string $labelKey): array
{
    $pairs = [];
    $result = $conn->query($sql);
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $id = (int) ($row[$idKey] ?? 0);
            if ($id > 0) {
                $label = trim((string) ($row[$labelKey] ?? ''));
                $pairs[$id] = $label !== '' ? $label : ('Record #' . $id);
            }
        }
        $result->close();
    }

    return $pairs;
}

function manager_next_id(mysqli $conn, string $table, string $column): int
{
    $nextId = 1;
    $result = $conn->query("SELECT COALESCE(MAX($column), 0) + 1 AS next_id FROM $table");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $nextId = max(1, (int) ($row['next_id'] ?? 1));
        $result->close();
    }

    return $nextId;
}

function manager_value(array $values, string $key, string $default = ''): string
{
    if (!array_key_exists($key, $values) || $values[$key] === null) {
        return $default;
    }

    return trim((string) $values[$key]);
}

function manager_excerpt($value, int $length = 90): string
{
    $text = trim((string) $value);
    if ($text === '') {
        return '—';
    }
    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, $length - 3)) . '...';
}

function manager_valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function manager_status_badge_class(string $status): string
{
    $key = strtolower(trim($status));
    if ($key === 'resolved') {
        return 'success';
    }
    if ($key === 'in progress') {
        return 'warning text-dark';
    }
    if ($key === 'closed') {
        return 'secondary';
    }

    return 'primary';
}

function manager_render_fields(
    string $entity,
    array $values,
    array $categoryOptions,
    array $serviceOptions,
    array $solutionOptions,
    array $statusOptions,
    string $prefix = 'manager'
): void {
    if ($entity === 'categories') {
        ?>
        <div class="row g-3">
            <div class="col-12">
                <label for="<?= manager_escape($prefix) ?>_category_name" class="form-label">Category Name *</label>
                <input type="text" id="<?= manager_escape($prefix) ?>_category_name" name="category_name" class="form-control" maxlength="100" required value="<?= manager_escape(manager_value($values, 'category_name')) ?>">
            </div>
            <div class="col-12">
                <label for="<?= manager_escape($prefix) ?>_description" class="form-label">Description</label>
                <textarea id="<?= manager_escape($prefix) ?>_description" name="description" class="form-control" rows="4" maxlength="1000"><?= manager_escape(manager_value($values, 'description')) ?></textarea>
            </div>
        </div>
        <?php
        return;
    }

    if ($entity === 'services') {
        ?>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_service_name" class="form-label">Service Name *</label>
                <input type="text" id="<?= manager_escape($prefix) ?>_service_name" name="service_name" class="form-control" maxlength="150" required value="<?= manager_escape(manager_value($values, 'service_name')) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_service_tier" class="form-label">Service Tier</label>
                <input type="text" id="<?= manager_escape($prefix) ?>_service_tier" name="service_tier" class="form-control" maxlength="50" placeholder="Basic, Standard, Premium" value="<?= manager_escape(manager_value($values, 'service_tier')) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_hourly_rate" class="form-label">Hourly Rate</label>
                <input type="number" id="<?= manager_escape($prefix) ?>_hourly_rate" name="hourly_rate" class="form-control" min="0" step="0.01" value="<?= manager_escape(manager_value($values, 'hourly_rate')) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_category_id" class="form-label">Category</label>
                <select id="<?= manager_escape($prefix) ?>_category_id" name="category_id" class="form-select">
                    <option value="">Select category</option>
                    <?php foreach ($categoryOptions as $id => $label): ?>
                    <option value="<?= manager_escape($id) ?>" <?= manager_value($values, 'category_id') === (string) $id ? 'selected' : '' ?>><?= manager_escape($label) ?> (#<?= manager_escape($id) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
        return;
    }

    if ($entity === 'solutions') {
        ?>
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label for="<?= manager_escape($prefix) ?>_title" class="form-label">Title *</label>
                <input type="text" id="<?= manager_escape($prefix) ?>_title" name="title" class="form-control" maxlength="255" required value="<?= manager_escape(manager_value($values, 'title')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="<?= manager_escape($prefix) ?>_date_created" class="form-label">Date Created *</label>
                <input type="date" id="<?= manager_escape($prefix) ?>_date_created" name="date_created" class="form-control" required value="<?= manager_escape(manager_value($values, 'date_created', date('Y-m-d'))) ?>">
            </div>
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_category_id" class="form-label">Category</label>
                <select id="<?= manager_escape($prefix) ?>_category_id" name="category_id" class="form-select">
                    <option value="">Select category</option>
                    <?php foreach ($categoryOptions as $id => $label): ?>
                    <option value="<?= manager_escape($id) ?>" <?= manager_value($values, 'category_id') === (string) $id ? 'selected' : '' ?>><?= manager_escape($label) ?> (#<?= manager_escape($id) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label for="<?= manager_escape($prefix) ?>_is_active" class="form-label">Visibility</label>
                <select id="<?= manager_escape($prefix) ?>_is_active" name="is_active" class="form-select">
                    <option value="1" <?= manager_value($values, 'is_active', '1') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= manager_value($values, 'is_active', '1') === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12">
                <label for="<?= manager_escape($prefix) ?>_description" class="form-label">Description</label>
                <textarea id="<?= manager_escape($prefix) ?>_description" name="description" class="form-control" rows="4"><?= manager_escape(manager_value($values, 'description')) ?></textarea>
            </div>
        </div>
        <?php
        return;
    }

    ?>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="<?= manager_escape($prefix) ?>_subject" class="form-label">Subject *</label>
            <input type="text" id="<?= manager_escape($prefix) ?>_subject" name="subject" class="form-control" maxlength="255" required value="<?= manager_escape(manager_value($values, 'subject')) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label for="<?= manager_escape($prefix) ?>_status" class="form-label">Status</label>
            <select id="<?= manager_escape($prefix) ?>_status" name="status" class="form-select">
                <?php foreach ($statusOptions as $status): ?>
                <option value="<?= manager_escape($status) ?>" <?= manager_value($values, 'status', 'Open') === $status ? 'selected' : '' ?>><?= manager_escape($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="<?= manager_escape($prefix) ?>_priority" class="form-label">Priority</label>
            <input type="number" id="<?= manager_escape($prefix) ?>_priority" name="priority" class="form-control" min="1" max="5" value="<?= manager_escape(manager_value($values, 'priority', '3')) ?>">
        </div>
        <div class="col-12 col-md-6">
            <label for="<?= manager_escape($prefix) ?>_solution_id" class="form-label">Linked Solution</label>
            <select id="<?= manager_escape($prefix) ?>_solution_id" name="solution_id" class="form-select">
                <option value="">Select solution</option>
                <?php foreach ($solutionOptions as $id => $label): ?>
                <option value="<?= manager_escape($id) ?>" <?= manager_value($values, 'solution_id') === (string) $id ? 'selected' : '' ?>><?= manager_escape($label) ?> (#<?= manager_escape($id) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label for="<?= manager_escape($prefix) ?>_service_id" class="form-label">Linked Service</label>
            <select id="<?= manager_escape($prefix) ?>_service_id" name="service_id" class="form-select">
                <option value="">Select service</option>
                <?php foreach ($serviceOptions as $id => $label): ?>
                <option value="<?= manager_escape($id) ?>" <?= manager_value($values, 'service_id') === (string) $id ? 'selected' : '' ?>><?= manager_escape($label) ?> (#<?= manager_escape($id) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php
}
$categoryOptions = manager_fetch_pairs($conn, "SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName ASC", 'CategoryID', 'CategoryName');
$serviceOptions = manager_fetch_pairs($conn, "SELECT ServiceID, ServiceName FROM services ORDER BY ServiceName ASC", 'ServiceID', 'ServiceName');
$solutionOptions = manager_fetch_pairs($conn, "SELECT SolutionID, Title FROM solutions ORDER BY Title ASC", 'SolutionID', 'Title');

$flash = $_SESSION['content_manager_flash'] ?? [];
if (($flash['entity'] ?? '') !== $entity) {
    $flash = [];
}
unset($_SESSION['content_manager_flash']);

$flashError = trim((string) ($flash['error'] ?? ''));
$flashSuccess = trim((string) ($flash['success'] ?? ''));
$flashModal = trim((string) ($flash['modal'] ?? ''));
$flashForm = is_array($flash['form'] ?? null) ? $flash['form'] : [];
$flashEditId = isset($flash['edit_id']) ? (int) $flash['edit_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectParams = [];
    $flashPayload = ['entity' => $entity];
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
        $flashPayload['error'] = 'Invalid request.';
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));
        if ($action === 'create') {
            if ($entity === 'categories') {
                $categoryName = trim((string) ($_POST['category_name'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                if ($categoryName === '') {
                    $flashPayload['error'] = 'Category name is required.';
                } elseif (strlen($categoryName) > 100) {
                    $flashPayload['error'] = 'Category name must be 100 characters or less.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (CategoryName, Description) VALUES (?, NULLIF(?, ''))");
                    if ($stmt) {
                        $stmt->bind_param('ss', $categoryName, $description);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Category created successfully.';
                            if (function_exists('audit_log')) {
                                audit_log('category_create', "Category {$categoryName} created", $uid);
                            }
                        } else {
                            $flashPayload['error'] = $stmt->errno === 1062 ? 'That category name already exists.' : 'Failed to create category.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare category creation.';
                    }
                }
            } elseif ($entity === 'services') {
                $serviceName = trim((string) ($_POST['service_name'] ?? ''));
                $serviceTier = trim((string) ($_POST['service_tier'] ?? ''));
                $hourlyRateInput = trim((string) ($_POST['hourly_rate'] ?? ''));
                $categoryIdInput = trim((string) ($_POST['category_id'] ?? ''));
                if ($serviceName === '') {
                    $flashPayload['error'] = 'Service name is required.';
                } elseif (strlen($serviceName) > 150) {
                    $flashPayload['error'] = 'Service name must be 150 characters or less.';
                } elseif ($serviceTier !== '' && strlen($serviceTier) > 50) {
                    $flashPayload['error'] = 'Service tier must be 50 characters or less.';
                } elseif ($hourlyRateInput !== '' && (!is_numeric($hourlyRateInput) || (float) $hourlyRateInput < 0)) {
                    $flashPayload['error'] = 'Hourly rate must be a positive number.';
                } elseif ($categoryIdInput !== '' && !isset($categoryOptions[(int) $categoryIdInput])) {
                    $flashPayload['error'] = 'Please select a valid category.';
                } else {
                    $hourlyRate = $hourlyRateInput !== '' ? number_format((float) $hourlyRateInput, 2, '.', '') : '';
                    $stmt = $conn->prepare("INSERT INTO services (ServiceName, ServiceTier, HourlyRate, CategoryID) VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
                    if ($stmt) {
                        $stmt->bind_param('ssss', $serviceName, $serviceTier, $hourlyRate, $categoryIdInput);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Service created successfully.';
                            if (function_exists('audit_log')) {
                                audit_log('service_create', "Service {$serviceName} created", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to create service.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare service creation.';
                    }
                }
            } elseif ($entity === 'solutions') {
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $categoryIdInput = trim((string) ($_POST['category_id'] ?? ''));
                $dateCreated = trim((string) ($_POST['date_created'] ?? date('Y-m-d')));
                $isActive = ($_POST['is_active'] ?? '1') === '0' ? 0 : 1;
                if ($title === '') {
                    $flashPayload['error'] = 'Solution title is required.';
                } elseif (strlen($title) > 255) {
                    $flashPayload['error'] = 'Solution title must be 255 characters or less.';
                } elseif (!manager_valid_date($dateCreated)) {
                    $flashPayload['error'] = 'Please provide a valid creation date.';
                } elseif ($categoryIdInput !== '' && !isset($categoryOptions[(int) $categoryIdInput])) {
                    $flashPayload['error'] = 'Please select a valid category.';
                } else {
                    $nextId = manager_next_id($conn, 'solutions', 'SolutionID');
                    $stmt = $conn->prepare("INSERT INTO solutions (SolutionID, Title, Description, CategoryID, DateCreated, IsActive) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param('issssi', $nextId, $title, $description, $categoryIdInput, $dateCreated, $isActive);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Solution created successfully.';
                            if (function_exists('audit_log')) {
                                audit_log('solution_create', "Solution {$title} created", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to create solution.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare solution creation.';
                    }
                }
            } else {
                $subject = trim((string) ($_POST['subject'] ?? ''));
                $status = trim((string) ($_POST['status'] ?? 'Open'));
                $priorityInput = trim((string) ($_POST['priority'] ?? '3'));
                $solutionIdInput = trim((string) ($_POST['solution_id'] ?? ''));
                $serviceIdInput = trim((string) ($_POST['service_id'] ?? ''));
                if ($subject === '') {
                    $flashPayload['error'] = 'Support subject is required.';
                } elseif (strlen($subject) > 255) {
                    $flashPayload['error'] = 'Support subject must be 255 characters or less.';
                } elseif (!in_array($status, $statusOptions, true)) {
                    $flashPayload['error'] = 'Please choose a valid support status.';
                } elseif (!ctype_digit($priorityInput) || (int) $priorityInput < 1 || (int) $priorityInput > 5) {
                    $flashPayload['error'] = 'Priority must be a number between 1 and 5.';
                } elseif ($solutionIdInput !== '' && !isset($solutionOptions[(int) $solutionIdInput])) {
                    $flashPayload['error'] = 'Please select a valid solution.';
                } elseif ($serviceIdInput !== '' && !isset($serviceOptions[(int) $serviceIdInput])) {
                    $flashPayload['error'] = 'Please select a valid service.';
                } else {
                    $priority = (int) $priorityInput;
                    $stmt = $conn->prepare("INSERT INTO support (Subject, Status, Priority, SolutionID, ServiceID) VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))");
                    if ($stmt) {
                        $stmt->bind_param('ssiss', $subject, $status, $priority, $solutionIdInput, $serviceIdInput);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Support record created successfully.';
                            if (function_exists('audit_log')) {
                                audit_log('support_create', "Support {$subject} created", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to create support record.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare support creation.';
                    }
                }
            }
            if (!isset($flashPayload['success'])) {
                $flashPayload['modal'] = 'create';
                $flashPayload['form'] = $_POST;
            }
        } elseif ($action === 'update') {
            $recordId = (int) ($_POST['record_id'] ?? 0);
            $redirectParams['edit_id'] = $recordId;
            if ($recordId <= 0) {
                $flashPayload['error'] = 'Invalid record selected for editing.';
            } elseif ($entity === 'categories') {
                $categoryName = trim((string) ($_POST['category_name'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                if ($categoryName === '') {
                    $flashPayload['error'] = 'Category name is required.';
                } elseif (strlen($categoryName) > 100) {
                    $flashPayload['error'] = 'Category name must be 100 characters or less.';
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET CategoryName = ?, Description = NULLIF(?, '') WHERE CategoryID = ?");
                    if ($stmt) {
                        $stmt->bind_param('ssi', $categoryName, $description, $recordId);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Category updated successfully.';
                            unset($redirectParams['edit_id']);
                            if (function_exists('audit_log')) {
                                audit_log('category_update', "Category {$recordId} updated", $uid);
                            }
                        } else {
                            $flashPayload['error'] = $stmt->errno === 1062 ? 'That category name already exists.' : 'Failed to update category.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare category update.';
                    }
                }
            } elseif ($entity === 'services') {
                $serviceName = trim((string) ($_POST['service_name'] ?? ''));
                $serviceTier = trim((string) ($_POST['service_tier'] ?? ''));
                $hourlyRateInput = trim((string) ($_POST['hourly_rate'] ?? ''));
                $categoryIdInput = trim((string) ($_POST['category_id'] ?? ''));
                if ($serviceName === '') {
                    $flashPayload['error'] = 'Service name is required.';
                } elseif (strlen($serviceName) > 150) {
                    $flashPayload['error'] = 'Service name must be 150 characters or less.';
                } elseif ($serviceTier !== '' && strlen($serviceTier) > 50) {
                    $flashPayload['error'] = 'Service tier must be 50 characters or less.';
                } elseif ($hourlyRateInput !== '' && (!is_numeric($hourlyRateInput) || (float) $hourlyRateInput < 0)) {
                    $flashPayload['error'] = 'Hourly rate must be a positive number.';
                } elseif ($categoryIdInput !== '' && !isset($categoryOptions[(int) $categoryIdInput])) {
                    $flashPayload['error'] = 'Please select a valid category.';
                } else {
                    $hourlyRate = $hourlyRateInput !== '' ? number_format((float) $hourlyRateInput, 2, '.', '') : '';
                    $stmt = $conn->prepare("UPDATE services SET ServiceName = ?, ServiceTier = NULLIF(?, ''), HourlyRate = NULLIF(?, ''), CategoryID = NULLIF(?, '') WHERE ServiceID = ?");
                    if ($stmt) {
                        $stmt->bind_param('ssssi', $serviceName, $serviceTier, $hourlyRate, $categoryIdInput, $recordId);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Service updated successfully.';
                            unset($redirectParams['edit_id']);
                            if (function_exists('audit_log')) {
                                audit_log('service_update', "Service {$recordId} updated", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to update service.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare service update.';
                    }
                }
            } elseif ($entity === 'solutions') {
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $categoryIdInput = trim((string) ($_POST['category_id'] ?? ''));
                $dateCreated = trim((string) ($_POST['date_created'] ?? ''));
                $isActive = ($_POST['is_active'] ?? '1') === '0' ? 0 : 1;
                if ($title === '') {
                    $flashPayload['error'] = 'Solution title is required.';
                } elseif (strlen($title) > 255) {
                    $flashPayload['error'] = 'Solution title must be 255 characters or less.';
                } elseif (!manager_valid_date($dateCreated)) {
                    $flashPayload['error'] = 'Please provide a valid creation date.';
                } elseif ($categoryIdInput !== '' && !isset($categoryOptions[(int) $categoryIdInput])) {
                    $flashPayload['error'] = 'Please select a valid category.';
                } else {
                    $stmt = $conn->prepare("UPDATE solutions SET Title = ?, Description = NULLIF(?, ''), CategoryID = NULLIF(?, ''), DateCreated = ?, IsActive = ? WHERE SolutionID = ?");
                    if ($stmt) {
                        $stmt->bind_param('ssssii', $title, $description, $categoryIdInput, $dateCreated, $isActive, $recordId);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Solution updated successfully.';
                            unset($redirectParams['edit_id']);
                            if (function_exists('audit_log')) {
                                audit_log('solution_update', "Solution {$recordId} updated", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to update solution.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare solution update.';
                    }
                }
            } else {
                $subject = trim((string) ($_POST['subject'] ?? ''));
                $status = trim((string) ($_POST['status'] ?? 'Open'));
                $priorityInput = trim((string) ($_POST['priority'] ?? '3'));
                $solutionIdInput = trim((string) ($_POST['solution_id'] ?? ''));
                $serviceIdInput = trim((string) ($_POST['service_id'] ?? ''));
                if ($subject === '') {
                    $flashPayload['error'] = 'Support subject is required.';
                } elseif (strlen($subject) > 255) {
                    $flashPayload['error'] = 'Support subject must be 255 characters or less.';
                } elseif (!in_array($status, $statusOptions, true)) {
                    $flashPayload['error'] = 'Please choose a valid support status.';
                } elseif (!ctype_digit($priorityInput) || (int) $priorityInput < 1 || (int) $priorityInput > 5) {
                    $flashPayload['error'] = 'Priority must be a number between 1 and 5.';
                } elseif ($solutionIdInput !== '' && !isset($solutionOptions[(int) $solutionIdInput])) {
                    $flashPayload['error'] = 'Please select a valid solution.';
                } elseif ($serviceIdInput !== '' && !isset($serviceOptions[(int) $serviceIdInput])) {
                    $flashPayload['error'] = 'Please select a valid service.';
                } else {
                    $priority = (int) $priorityInput;
                    $stmt = $conn->prepare("UPDATE support SET Subject = ?, Status = ?, Priority = ?, SolutionID = NULLIF(?, ''), ServiceID = NULLIF(?, '') WHERE ID = ?");
                    if ($stmt) {
                        $stmt->bind_param('ssissi', $subject, $status, $priority, $solutionIdInput, $serviceIdInput, $recordId);
                        if ($stmt->execute()) {
                            $flashPayload['success'] = 'Support record updated successfully.';
                            unset($redirectParams['edit_id']);
                            if (function_exists('audit_log')) {
                                audit_log('support_update', "Support {$recordId} updated", $uid);
                            }
                        } else {
                            $flashPayload['error'] = 'Failed to update support record.';
                        }
                        $stmt->close();
                    } else {
                        $flashPayload['error'] = 'Failed to prepare support update.';
                    }
                }
            }
            if (!isset($flashPayload['success'])) {
                $flashPayload['edit_id'] = $recordId;
                $flashPayload['form'] = $_POST;
            }
        } elseif ($action === 'delete') {
            $recordId = (int) ($_POST['record_id'] ?? 0);
            if ($recordId <= 0) {
                $flashPayload['error'] = 'Invalid record selected for deletion.';
            } else {
                $stmt = $conn->prepare("DELETE FROM {$meta['table']} WHERE {$meta['pk']} = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $recordId);
                    if ($stmt->execute()) {
                        $flashPayload['success'] = $meta['singular'] . ' deleted successfully.';
                        if (function_exists('audit_log')) {
                            audit_log(strtolower(str_replace(' ', '_', $meta['singular'])) . '_delete', "{$meta['singular']} {$recordId} deleted", $uid);
                        }
                    } else {
                        $flashPayload['error'] = in_array($stmt->errno, [1451, 1452], true)
                            ? 'This record cannot be deleted because other records still reference it.'
                            : 'Failed to delete the selected record.';
                    }
                    $stmt->close();
                } else {
                    $flashPayload['error'] = 'Failed to prepare record deletion.';
                }
            }
        } else {
            $flashPayload['error'] = 'Unsupported action requested.';
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    $_SESSION['content_manager_flash'] = $flashPayload;
    header('Location: ' . manager_url($entity, $redirectParams));
    exit();
}

$defaultCreateValues = [
    'categories' => ['category_name' => '', 'description' => ''],
    'services' => ['service_name' => '', 'service_tier' => '', 'hourly_rate' => '', 'category_id' => ''],
    'solutions' => ['title' => '', 'description' => '', 'category_id' => '', 'date_created' => date('Y-m-d'), 'is_active' => '1'],
    'support' => ['subject' => '', 'status' => 'Open', 'priority' => '3', 'solution_id' => '', 'service_id' => ''],
];
$createValues = $defaultCreateValues[$entity];
if ($flashModal === 'create' && !empty($flashForm)) {
    $createValues = array_merge($createValues, $flashForm);
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    if ($editId > 0) {
        if ($entity === 'categories') {
            $stmt = $conn->prepare("SELECT CategoryID AS record_id, CategoryName AS category_name, COALESCE(Description, '') AS description FROM categories WHERE CategoryID = ?");
        } elseif ($entity === 'services') {
            $stmt = $conn->prepare("SELECT ServiceID AS record_id, ServiceName AS service_name, COALESCE(ServiceTier, '') AS service_tier, COALESCE(CAST(HourlyRate AS CHAR), '') AS hourly_rate, COALESCE(CategoryID, '') AS category_id FROM services WHERE ServiceID = ?");
        } elseif ($entity === 'solutions') {
            $stmt = $conn->prepare("SELECT SolutionID AS record_id, Title AS title, COALESCE(Description, '') AS description, COALESCE(CategoryID, '') AS category_id, DateCreated AS date_created, IF(IsActive = b'1', '1', '0') AS is_active FROM solutions WHERE SolutionID = ?");
        } else {
            $stmt = $conn->prepare("SELECT ID AS record_id, Subject AS subject, Status AS status, Priority AS priority, COALESCE(SolutionID, '') AS solution_id, COALESCE(ServiceID, '') AS service_id FROM support WHERE ID = ?");
        }
        if ($stmt) {
            $stmt->bind_param('i', $editId);
            $stmt->execute();
            $editRecord = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();
        }
    }
}

$editValues = [];
if ($editRecord) {
    $editValues = $editRecord;
    if ($flashEditId === (int) ($editRecord['record_id'] ?? 0) && !empty($flashForm)) {
        $editValues = array_merge($editValues, $flashForm);
    }
}

$recordCount = 0;
$listResult = false;
if ($entity === 'categories') {
    $listResult = $conn->query("SELECT CategoryID, CategoryName, Description FROM categories ORDER BY CategoryName ASC");
} elseif ($entity === 'services') {
    $listResult = $conn->query("SELECT s.ServiceID, s.ServiceName, s.ServiceTier, s.HourlyRate, c.CategoryName FROM services s LEFT JOIN categories c ON c.CategoryID = s.CategoryID ORDER BY s.ServiceName ASC");
} elseif ($entity === 'solutions') {
    $listResult = $conn->query("SELECT sol.SolutionID, sol.Title, sol.Description, sol.DateCreated, IF(sol.IsActive = b'1', 1, 0) AS IsActiveValue, c.CategoryName FROM solutions sol LEFT JOIN categories c ON c.CategoryID = sol.CategoryID ORDER BY sol.DateCreated DESC, sol.Title ASC");
} else {
    $listResult = $conn->query("SELECT sp.ID, sp.Subject, sp.Status, sp.Priority, sp.CreatedAt, sol.Title AS SolutionTitle, svc.ServiceName FROM support sp LEFT JOIN solutions sol ON sol.SolutionID = sp.SolutionID LEFT JOIN services svc ON svc.ServiceID = sp.ServiceID ORDER BY sp.CreatedAt DESC, sp.ID DESC");
}
if ($listResult instanceof mysqli_result) {
    $recordCount = (int) $listResult->num_rows;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= manager_escape($meta['title']) ?> - NexGen Solution</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/css/theme.css" rel="stylesheet">
    <link href="/css/components.css" rel="stylesheet">
    <link href="/css/ui-universal.css" rel="stylesheet">
    <style>
    .manager-summary-card { border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1rem; padding: 1rem 1.25rem; background: rgba(15, 23, 42, 0.72); margin-bottom: 1.5rem; }
    .manager-summary-card strong { display: block; font-size: 1.7rem; line-height: 1; margin-bottom: 0.35rem; }
    .manager-panel { border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1rem; background: rgba(15, 23, 42, 0.72); padding: 1.25rem; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2); }
    .manager-table td, .manager-table th { vertical-align: middle; }
    .manager-search { max-width: 22rem; }
    .manager-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    @media (max-width: 768px) { .manager-search { max-width: 100%; width: 100%; } }
    </style>
</head>
<body class="future-page future-dashboard" data-theme="dark">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <button class="sidebar-toggle" id="sidebarToggleBtn" type="button"><i class="bi bi-list"></i></button>
    <div class="main-wrapper">
        <div id="sidebarContainer"><?php render_sidebar(); ?></div>
        <div class="main-content">
            <div class="dashboard-shell">
                <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3><i class="bi <?= manager_escape($meta['icon']) ?>"></i> <?= manager_escape($meta['title']) ?></h3>
                        <p><?= manager_escape($meta['subtitle']) ?></p>
                    </div>
                    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createManagerModal"><i class="bi bi-plus-circle"></i> Add <?= manager_escape($meta['singular']) ?></button>
                </div>
                <div class="manager-summary-card">
                    <strong><?= manager_escape($recordCount) ?></strong>
                    <span><?= manager_escape($meta['plural']) ?> currently in the admin catalog.</span>
                </div>
                <?php if ($flashError !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?= manager_escape($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if ($flashSuccess !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= manager_escape($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if ($editRecord): ?>
                <div class="manager-panel mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="mb-1">Edit <?= manager_escape($meta['singular']) ?></h5>
                            <small class="text-muted">Record #<?= manager_escape($editRecord['record_id'] ?? '') ?></small>
                        </div>
                        <a href="<?= manager_escape(manager_url($entity)) ?>" class="btn btn-outline-secondary btn-sm">Cancel Edit</a>
                    </div>
                    <form method="post" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="record_id" value="<?= manager_escape($editRecord['record_id'] ?? '') ?>">
                        <?php manager_render_fields($entity, $editValues, $categoryOptions, $serviceOptions, $solutionOptions, $statusOptions, 'edit'); ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary-custom">Update <?= manager_escape($meta['singular']) ?></button>
                            <a href="<?= manager_escape(manager_url($entity)) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                <div class="manager-panel">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-1"><?= manager_escape($meta['plural']) ?> List</h5>
                            <small class="text-muted">Search across the currently loaded records.</small>
                        </div>
                        <div class="manager-search">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="managerSearch" placeholder="Search <?= manager_escape(strtolower($meta['plural'])) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle manager-table mb-0">
                            <thead class="table-primary">
                                <?php if ($entity === 'categories'): ?>
                                <tr><th>ID</th><th>Category Name</th><th>Description</th><th>Actions</th></tr>
                                <?php elseif ($entity === 'services'): ?>
                                <tr><th>ID</th><th>Service</th><th>Tier</th><th>Category</th><th>Rate</th><th>Actions</th></tr>
                                <?php elseif ($entity === 'solutions'): ?>
                                <tr><th>ID</th><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th>Description</th><th>Actions</th></tr>
                                <?php else: ?>
                                <tr><th>ID</th><th>Subject</th><th>Status</th><th>Priority</th><th>Solution</th><th>Service</th><th>Created</th><th>Actions</th></tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php if (!$listResult instanceof mysqli_result || $recordCount === 0): ?>
                                <tr>
                                    <td colspan="<?= $entity === 'categories' ? '4' : ($entity === 'services' ? '6' : ($entity === 'solutions' ? '7' : '8')) ?>" class="text-center text-muted py-4">
                                        No <?= manager_escape(strtolower($meta['plural'])) ?> available yet.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php while ($row = $listResult->fetch_assoc()): ?>
                                <tr data-manager-row="1">
                                    <?php if ($entity === 'categories'): ?>
                                    <td><?= manager_escape($row['CategoryID'] ?? '') ?></td>
                                    <td><?= manager_escape($row['CategoryName'] ?? '') ?></td>
                                    <td><?= manager_escape(manager_excerpt($row['Description'] ?? '')) ?></td>
                                    <td>
                                        <div class="manager-actions">
                                            <a href="<?= manager_escape(manager_url($entity, ['edit_id' => (int) ($row['CategoryID'] ?? 0)])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                            <form method="post" onsubmit="return confirm('Delete this category? Related services or solutions may block this action.')">
                                                <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="record_id" value="<?= manager_escape($row['CategoryID'] ?? '') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                    <?php elseif ($entity === 'services'): ?>
                                    <td><?= manager_escape($row['ServiceID'] ?? '') ?></td>
                                    <td><?= manager_escape($row['ServiceName'] ?? '') ?></td>
                                    <td><?= manager_escape($row['ServiceTier'] ?? '-') ?></td>
                                    <td><?= manager_escape($row['CategoryName'] ?? 'Unassigned') ?></td>
                                    <td><?= $row['HourlyRate'] !== null ? '$' . manager_escape(number_format((float) $row['HourlyRate'], 2)) : '-' ?></td>
                                    <td>
                                        <div class="manager-actions">
                                            <a href="<?= manager_escape(manager_url($entity, ['edit_id' => (int) ($row['ServiceID'] ?? 0)])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                            <form method="post" onsubmit="return confirm('Delete this service? Linked support records may block this action.')">
                                                <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="record_id" value="<?= manager_escape($row['ServiceID'] ?? '') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                    <?php elseif ($entity === 'solutions'): ?>
                                    <td><?= manager_escape($row['SolutionID'] ?? '') ?></td>
                                    <td><?= manager_escape($row['Title'] ?? '') ?></td>
                                    <td><?= manager_escape($row['CategoryName'] ?? 'Unassigned') ?></td>
                                    <td><?= manager_escape($row['DateCreated'] ?? '') ?></td>
                                    <td><span class="badge bg-<?= (int) ($row['IsActiveValue'] ?? 0) === 1 ? 'success' : 'secondary' ?>"><?= (int) ($row['IsActiveValue'] ?? 0) === 1 ? 'Active' : 'Inactive' ?></span></td>
                                    <td><?= manager_escape(manager_excerpt($row['Description'] ?? '')) ?></td>
                                    <td>
                                        <div class="manager-actions">
                                            <a href="<?= manager_escape(manager_url($entity, ['edit_id' => (int) ($row['SolutionID'] ?? 0)])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                            <form method="post" onsubmit="return confirm('Delete this solution? Linked support records may block this action.')">
                                                <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="record_id" value="<?= manager_escape($row['SolutionID'] ?? '') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                    <?php else: ?>
                                    <td><?= manager_escape($row['ID'] ?? '') ?></td>
                                    <td><?= manager_escape($row['Subject'] ?? '') ?></td>
                                    <td><span class="badge bg-<?= manager_escape(manager_status_badge_class((string) ($row['Status'] ?? 'Open'))) ?>"><?= manager_escape($row['Status'] ?? 'Open') ?></span></td>
                                    <td><?= manager_escape($row['Priority'] ?? '') ?></td>
                                    <td><?= manager_escape($row['SolutionTitle'] ?? 'Unassigned') ?></td>
                                    <td><?= manager_escape($row['ServiceName'] ?? 'Unassigned') ?></td>
                                    <td><?= manager_escape($row['CreatedAt'] ?? '') ?></td>
                                    <td>
                                        <div class="manager-actions">
                                            <a href="<?= manager_escape(manager_url($entity, ['edit_id' => (int) ($row['ID'] ?? 0)])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                            <form method="post" onsubmit="return confirm('Delete this support record?')">
                                                <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="record_id" value="<?= manager_escape($row['ID'] ?? '') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                                <tr id="managerNoResultsRow" class="d-none">
                                    <td colspan="<?= $entity === 'categories' ? '4' : ($entity === 'services' ? '6' : ($entity === 'solutions' ? '7' : '8')) ?>" class="text-center text-muted py-4">
                                        No matching <?= manager_escape(strtolower($meta['plural'])) ?> found.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createManagerModal" tabindex="-1" aria-labelledby="createManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createManagerModalLabel">Add <?= manager_escape($meta['singular']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= manager_escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="create">
                        <?php manager_render_fields($entity, $createValues, $categoryOptions, $serviceOptions, $solutionOptions, $statusOptions, 'create'); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Create <?= manager_escape($meta['singular']) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const nexgenSidebar = document.getElementById('nexgenSidebar');
        const managerSearch = document.getElementById('managerSearch');
        const managerRows = document.querySelectorAll('tr[data-manager-row="1"]');
        const noResultsRow = document.getElementById('managerNoResultsRow');

        if (sidebarToggleBtn && nexgenSidebar) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                nexgenSidebar.classList.toggle('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show');
                }
            });
        }

        if (sidebarOverlay && nexgenSidebar) {
            sidebarOverlay.addEventListener('click', function() {
                nexgenSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        if (nexgenSidebar) {
            document.querySelectorAll('.nexgen-sidebar-menu a').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        nexgenSidebar.classList.remove('show');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('show');
                        }
                    }
                });
            });
        }

        if (managerSearch) {
            managerSearch.addEventListener('input', function() {
                const query = managerSearch.value.toLowerCase().trim();
                let visibleCount = 0;
                managerRows.forEach(row => {
                    const match = row.textContent.toLowerCase().includes(query);
                    row.classList.toggle('d-none', !match);
                    if (match) {
                        visibleCount += 1;
                    }
                });
                if (noResultsRow) {
                    noResultsRow.classList.toggle('d-none', visibleCount !== 0 || query === '');
                }
            });
        }

        const shouldOpenCreateModal = <?= $flashModal === 'create' ? 'true' : 'false' ?>;
        if (shouldOpenCreateModal) {
            const modalEl = document.getElementById('createManagerModal');
            if (modalEl && window.bootstrap) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        }
    });
    </script>
</body>
</html>
