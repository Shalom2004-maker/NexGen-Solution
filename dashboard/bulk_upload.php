<?php
include "../includes/auth.php";
allow(["Admin", "HR", "ProjectLeader"]);
include "../includes/db.php";
require_once __DIR__ . "/../includes/logger.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

$uid = (int)($_SESSION['uid'] ?? 0);
$allowedTypes = ['users', 'employees', 'leave_requests', 'payroll_inputs', 'projects', 'tasks'];

function normalize_header(string $header): string
{
    $h = strtolower(trim($header));
    $h = str_replace([' ', '-', '.'], '_', $h);
    return $h;
}

function is_valid_date(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return $dt && $dt->format('Y-m-d') === $value;
}

// CSV template download
if (isset($_GET['download']) && $_GET['download'] === 'template') {
    $type = $_GET['type'] ?? '';
    if (in_array($type, $allowedTypes, true)) {
        $templates = [
            'users' => [
                ['full_name', 'email', 'password', 'role', 'status'],
                ['Jane Doe', 'jane@example.com', 'password123', 'Employee', 'active'],
            ],
            'employees' => [
                ['user_email', 'job_title', 'department', 'hire_date', 'salary_base', 'status'],
                ['jane@example.com', 'Designer', 'Design', '2026-02-01', '55000', 'active'],
            ],
            'leave_requests' => [
                ['employee_email', 'start_date', 'end_date', 'leave_type', 'reason', 'status', 'leader_email', 'hr_email'],
                ['jane@example.com', '2026-02-10', '2026-02-12', 'annual', 'Family trip', 'pending', '', ''],
            ],
            'payroll_inputs' => [
                ['employee_email', 'month', 'year', 'overtime_hours', 'bonus', 'deductions', 'status', 'submitted_by_email'],
                ['jane@example.com', '2', '2026', '5.5', '1500', '0', 'pending', 'leader@spycray.com'],
            ],
            'projects' => [
                ['project_name', 'description', 'leader_email', 'start_date', 'end_date'],
                ['New Website', 'Marketing site refresh', 'leader@spycray.com', '2026-03-01', '2026-06-01'],
            ],
            'tasks' => [
                ['project_name', 'assigned_to_email', 'created_by_email', 'title', 'description', 'status', 'deadline'],
                ['New Website', 'jane@example.com', 'leader@spycray.com', 'Wireframe homepage', 'Initial layout', 'todo', '2026-03-15'],
            ],
        ];
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_' . $type . '.csv"');
        $out = fopen('php://output', 'w');
        foreach ($templates[$type] as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

// Error report download
if (isset($_GET['download']) && $_GET['download'] === 'errors') {
    $csv = $_SESSION['bulk_upload_errors_csv'] ?? '';
    if ($csv !== '') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bulk_upload_errors.csv"');
        echo $csv;
        exit;
    }
}

$error = '';
$success = '';
$errors = [];
$processed = 0;
$inserted = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_upload'])) {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $error = 'Invalid request (CSRF).';
    } else {
        $type = $_POST['data_type'] ?? '';
        if (!in_array($type, $allowedTypes, true)) {
            $error = 'Invalid data type selected.';
        } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload a valid CSV file.';
        } else {
            $fileName = $_FILES['csv_file']['name'] ?? '';
            if (!preg_match('/\\.csv$/i', $fileName)) {
                $error = 'Only CSV files are supported.';
            }
        }
    }

    if ($error === '') {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpName, 'r');
        if ($handle === false) {
            $error = 'Failed to open uploaded file.';
        } else {
            $header = fgetcsv($handle);
            if (!$header) {
                $error = 'CSV header row is missing.';
            } else {
                $header = array_map('normalize_header', $header);
                $headerMap = array_flip($header);

                // Preload role map
                $roleMap = [];
                $roleRes = $conn->query("SELECT id, role_name FROM roles");
                if ($roleRes) {
                    while ($r = $roleRes->fetch_assoc()) {
                        $roleMap[strtolower($r['role_name'])] = (int)$r['id'];
                    }
                }

                $rowIndex = 1;

                if ($type === 'users') {
                    $checkUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $insertUser = $conn->prepare("INSERT INTO users(full_name,email,password_hash,role_id,status) VALUES(?,?,?,?,?)");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $name = trim($data['full_name'] ?? '');
                        $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        $password = trim($data['password'] ?? '');
                        $roleInput = trim($data['role'] ?? ($data['role_id'] ?? ''));
                        $status = strtolower(trim($data['status'] ?? 'active'));

                        if ($name === '' || !$email || $password === '' || $roleInput === '') {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Missing required fields (full_name,email,password,role).', 'data' => $row];
                            continue;
                        }

                        if (!in_array($status, ['active', 'disabled'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid status (active/disabled).', 'data' => $row];
                            continue;
                        }

                        $roleId = 0;
                        if (is_numeric($roleInput)) {
                            $roleId = (int)$roleInput;
                        } else {
                            $roleId = $roleMap[strtolower($roleInput)] ?? 0;
                        }
                        if ($roleId <= 0) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid role.', 'data' => $row];
                            continue;
                        }

                        $checkUser->bind_param('s', $email);
                        $checkUser->execute();
                        $exists = $checkUser->get_result()->num_rows > 0;
                        if ($exists) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Email already exists.', 'data' => $row];
                            continue;
                        }

                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $insertUser->bind_param('sssis', $name, $email, $hash, $roleId, $status);
                        if ($insertUser->execute() && $insertUser->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert user.', 'data' => $row];
                        }
                    }

                    $checkUser->close();
                    $insertUser->close();
                } elseif ($type === 'employees') {
                    $findUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $checkEmp = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
                    $insertEmp = $conn->prepare("INSERT INTO employees(user_id,job_title,department,hire_date,salary_base,status) VALUES(?,?,?,?,?,?)");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $email = filter_var(trim($data['user_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        if (!$email) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid user_email.', 'data' => $row];
                            continue;
                        }

                        $findUser->bind_param('s', $email);
                        $findUser->execute();
                        $userRow = $findUser->get_result()->fetch_assoc();
                        if (!$userRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'User not found for user_email.', 'data' => $row];
                            continue;
                        }
                        $userId = (int)$userRow['id'];

                        $checkEmp->bind_param('i', $userId);
                        $checkEmp->execute();
                        if ($checkEmp->get_result()->num_rows > 0) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Employee record already exists for this user.', 'data' => $row];
                            continue;
                        }

                        $jobTitle = trim($data['job_title'] ?? '');
                        $department = trim($data['department'] ?? '');
                        $hireDate = trim($data['hire_date'] ?? '');
                        $salaryBase = trim($data['salary_base'] ?? '');
                        $status = strtolower(trim($data['status'] ?? 'active'));
                        if ($hireDate !== '' && !is_valid_date($hireDate)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid hire_date (YYYY-MM-DD).', 'data' => $row];
                            continue;
                        }
                        if ($salaryBase !== '' && !is_numeric($salaryBase)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid salary_base.', 'data' => $row];
                            continue;
                        }
                        if (!in_array($status, ['active', 'resigned'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid status (active/resigned).', 'data' => $row];
                            continue;
                        }

                        $salaryBaseVal = $salaryBase === '' ? null : (float)$salaryBase;
                        $hireDateVal = $hireDate === '' ? null : $hireDate;
                        $insertEmp->bind_param('isssds', $userId, $jobTitle, $department, $hireDateVal, $salaryBaseVal, $status);
                        if ($insertEmp->execute() && $insertEmp->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert employee.', 'data' => $row];
                        }
                    }

                    $findUser->close();
                    $checkEmp->close();
                    $insertEmp->close();
                } elseif ($type === 'leave_requests') {
                    $findEmployee = $conn->prepare("SELECT e.id FROM employees e JOIN users u ON e.user_id = u.id WHERE u.email = ?");
                    $findUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $insertLeave = $conn->prepare("INSERT INTO leave_requests(employee_id,start_date,end_date,leave_type,reason,status,leader_id,hr_id) VALUES(?,?,?,?,?,?,NULLIF(?,0),NULLIF(?,0))");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $email = filter_var(trim($data['employee_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        $start = trim($data['start_date'] ?? '');
                        $end = trim($data['end_date'] ?? '');
                        $leaveType = strtolower(trim($data['leave_type'] ?? ''));
                        $reason = trim($data['reason'] ?? '');
                        $status = strtolower(trim($data['status'] ?? 'pending'));

                        if (!$email || !is_valid_date($start) || !is_valid_date($end) || $leaveType === '' || $reason === '') {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Missing/invalid required fields.', 'data' => $row];
                            continue;
                        }
                        if (!in_array($leaveType, ['sick', 'annual', 'unpaid', 'personal', 'vacation'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid leave_type.', 'data' => $row];
                            continue;
                        }
                        if (!in_array($status, ['pending', 'leader_approved', 'hr_approved', 'rejected'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid status.', 'data' => $row];
                            continue;
                        }

                        $findEmployee->bind_param('s', $email);
                        $findEmployee->execute();
                        $empRow = $findEmployee->get_result()->fetch_assoc();
                        if (!$empRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Employee not found for employee_email.', 'data' => $row];
                            continue;
                        }
                        $employeeId = (int)$empRow['id'];

                        $leaderId = 0;
                        $leaderEmail = trim($data['leader_email'] ?? '');
                        if ($leaderEmail !== '') {
                            $leaderEmail = filter_var($leaderEmail, FILTER_VALIDATE_EMAIL);
                            if (!$leaderEmail) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Invalid leader_email.', 'data' => $row];
                                continue;
                            }
                            $findUser->bind_param('s', $leaderEmail);
                            $findUser->execute();
                            $leaderRow = $findUser->get_result()->fetch_assoc();
                            $leaderId = $leaderRow ? (int)$leaderRow['id'] : 0;
                            if ($leaderId <= 0) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Leader user not found.', 'data' => $row];
                                continue;
                            }
                        }

                        $hrId = 0;
                        $hrEmail = trim($data['hr_email'] ?? '');
                        if ($hrEmail !== '') {
                            $hrEmail = filter_var($hrEmail, FILTER_VALIDATE_EMAIL);
                            if (!$hrEmail) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Invalid hr_email.', 'data' => $row];
                                continue;
                            }
                            $findUser->bind_param('s', $hrEmail);
                            $findUser->execute();
                            $hrRow = $findUser->get_result()->fetch_assoc();
                            $hrId = $hrRow ? (int)$hrRow['id'] : 0;
                            if ($hrId <= 0) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'HR user not found.', 'data' => $row];
                                continue;
                            }
                        }

                        if ($status === 'leader_approved' && $leaderId <= 0) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'leader_approved requires leader_email.', 'data' => $row];
                            continue;
                        }
                        if ($status === 'hr_approved' && $hrId <= 0) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'hr_approved requires hr_email.', 'data' => $row];
                            continue;
                        }

                        $insertLeave->bind_param('isssssis', $employeeId, $start, $end, $leaveType, $reason, $status, $leaderId, $hrId);
                        if ($insertLeave->execute() && $insertLeave->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert leave request.', 'data' => $row];
                        }
                    }

                    $findEmployee->close();
                    $findUser->close();
                    $insertLeave->close();
                } elseif ($type === 'payroll_inputs') {
                    $findEmployee = $conn->prepare("SELECT e.id FROM employees e JOIN users u ON e.user_id = u.id WHERE u.email = ?");
                    $findUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $insertPayroll = $conn->prepare("INSERT INTO payroll_inputs(employee_id,month,year,overtime_hours,bonus,deductions,submitted_by,status) VALUES(?,?,?,?,?,?,NULLIF(?,0),?)");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $email = filter_var(trim($data['employee_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        $month = trim($data['month'] ?? '');
                        $year = trim($data['year'] ?? '');
                        $overtime = trim($data['overtime_hours'] ?? '');
                        $bonus = trim($data['bonus'] ?? '');
                        $deductions = trim($data['deductions'] ?? '');
                        $status = strtolower(trim($data['status'] ?? 'pending'));
                        $submittedByEmail = trim($data['submitted_by_email'] ?? '');

                        if (!$email || !is_numeric($month) || !is_numeric($year)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Missing/invalid required fields (employee_email,month,year).', 'data' => $row];
                            continue;
                        }
                        $month = (int)$month;
                        $year = (int)$year;
                        if ($month < 1 || $month > 12) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid month (1-12).', 'data' => $row];
                            continue;
                        }
                        if ($year < 2000 || $year > 2100) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid year.', 'data' => $row];
                            continue;
                        }
                        if ($overtime !== '' && !is_numeric($overtime)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid overtime_hours.', 'data' => $row];
                            continue;
                        }
                        if ($bonus !== '' && !is_numeric($bonus)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid bonus.', 'data' => $row];
                            continue;
                        }
                        if ($deductions !== '' && !is_numeric($deductions)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid deductions.', 'data' => $row];
                            continue;
                        }
                        if (!in_array($status, ['pending', 'approved'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid status.', 'data' => $row];
                            continue;
                        }

                        $findEmployee->bind_param('s', $email);
                        $findEmployee->execute();
                        $empRow = $findEmployee->get_result()->fetch_assoc();
                        if (!$empRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Employee not found for employee_email.', 'data' => $row];
                            continue;
                        }
                        $employeeId = (int)$empRow['id'];

                        $submittedBy = $uid;
                        if ($submittedByEmail !== '') {
                            $submittedByEmail = filter_var($submittedByEmail, FILTER_VALIDATE_EMAIL);
                            if (!$submittedByEmail) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Invalid submitted_by_email.', 'data' => $row];
                                continue;
                            }
                            $findUser->bind_param('s', $submittedByEmail);
                            $findUser->execute();
                            $subRow = $findUser->get_result()->fetch_assoc();
                            $submittedBy = $subRow ? (int)$subRow['id'] : 0;
                            if ($submittedBy <= 0) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Submitted_by user not found.', 'data' => $row];
                                continue;
                            }
                        }

                        $overtimeVal = $overtime === '' ? null : (float)$overtime;
                        $bonusVal = $bonus === '' ? null : (float)$bonus;
                        $deductionsVal = $deductions === '' ? null : (float)$deductions;

                        $insertPayroll->bind_param(
                            'iiidddis',
                            $employeeId,
                            $month,
                            $year,
                            $overtimeVal,
                            $bonusVal,
                            $deductionsVal,
                            $submittedBy,
                            $status
                        );
                        if ($insertPayroll->execute() && $insertPayroll->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert payroll input.', 'data' => $row];
                        }
                    }

                    $findEmployee->close();
                    $findUser->close();
                    $insertPayroll->close();
                } elseif ($type === 'projects') {
                    $findUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $insertProject = $conn->prepare("INSERT INTO projects(project_name,description,leader_id,start_date,end_date) VALUES(?,?,NULLIF(?,0),?,?)");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $name = trim($data['project_name'] ?? '');
                        $desc = trim($data['description'] ?? '');
                        $leaderEmail = trim($data['leader_email'] ?? '');
                        $start = trim($data['start_date'] ?? '');
                        $end = trim($data['end_date'] ?? '');

                        if ($name === '' || !is_valid_date($start) || !is_valid_date($end)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Missing/invalid required fields (project_name,start_date,end_date).', 'data' => $row];
                            continue;
                        }

                        $leaderId = 0;
                        if ($leaderEmail !== '') {
                            $leaderEmail = filter_var($leaderEmail, FILTER_VALIDATE_EMAIL);
                            if (!$leaderEmail) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Invalid leader_email.', 'data' => $row];
                                continue;
                            }
                            $findUser->bind_param('s', $leaderEmail);
                            $findUser->execute();
                            $leaderRow = $findUser->get_result()->fetch_assoc();
                            $leaderId = $leaderRow ? (int)$leaderRow['id'] : 0;
                            if ($leaderId <= 0) {
                                $errors[] = ['row' => $rowIndex, 'error' => 'Leader user not found.', 'data' => $row];
                                continue;
                            }
                        }

                        $insertProject->bind_param('ssiss', $name, $desc, $leaderId, $start, $end);
                        if ($insertProject->execute() && $insertProject->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert project.', 'data' => $row];
                        }
                    }

                    $findUser->close();
                    $insertProject->close();
                } elseif ($type === 'tasks') {
                    $findProject = $conn->prepare("SELECT id FROM projects WHERE project_name = ?");
                    $findUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $insertTask = $conn->prepare("INSERT INTO tasks(project_id,assigned_to,created_by,title,description,status,deadline) VALUES(?,?,?,?,?,?,?)");

                    while (($row = fgetcsv($handle)) !== false) {
                        $rowIndex++;
                        $processed++;
                        $data = [];
                        foreach ($headerMap as $key => $idx) {
                            $data[$key] = $row[$idx] ?? '';
                        }

                        $projectName = trim($data['project_name'] ?? '');
                        $assignedEmail = filter_var(trim($data['assigned_to_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        $createdEmail = filter_var(trim($data['created_by_email'] ?? ''), FILTER_VALIDATE_EMAIL);
                        $title = trim($data['title'] ?? '');
                        $desc = trim($data['description'] ?? '');
                        $status = strtolower(trim($data['status'] ?? 'todo'));
                        $deadline = trim($data['deadline'] ?? '');

                        if ($projectName === '' || !$assignedEmail || !$createdEmail || $title === '' || !is_valid_date($deadline)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Missing/invalid required fields (project_name,assigned_to_email,created_by_email,title,deadline).', 'data' => $row];
                            continue;
                        }
                        if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Invalid status (todo/in_progress/done).', 'data' => $row];
                            continue;
                        }

                        $findProject->bind_param('s', $projectName);
                        $findProject->execute();
                        $projRow = $findProject->get_result()->fetch_assoc();
                        if (!$projRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Project not found for project_name.', 'data' => $row];
                            continue;
                        }
                        $projectId = (int)$projRow['id'];

                        $findUser->bind_param('s', $assignedEmail);
                        $findUser->execute();
                        $assignedRow = $findUser->get_result()->fetch_assoc();
                        if (!$assignedRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Assigned user not found.', 'data' => $row];
                            continue;
                        }
                        $assignedId = (int)$assignedRow['id'];

                        $findUser->bind_param('s', $createdEmail);
                        $findUser->execute();
                        $createdRow = $findUser->get_result()->fetch_assoc();
                        if (!$createdRow) {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Created_by user not found.', 'data' => $row];
                            continue;
                        }
                        $createdId = (int)$createdRow['id'];

                        $insertTask->bind_param('iiissss', $projectId, $assignedId, $createdId, $title, $desc, $status, $deadline);
                        if ($insertTask->execute() && $insertTask->affected_rows > 0) {
                            $inserted++;
                        } else {
                            $errors[] = ['row' => $rowIndex, 'error' => 'Failed to insert task.', 'data' => $row];
                        }
                    }

                    $findProject->close();
                    $findUser->close();
                    $insertTask->close();
                }

                fclose($handle);

                if ($inserted > 0) {
                    $success = "Uploaded {$inserted} record(s).";
                    if (function_exists('audit_log')) {
                        audit_log('bulk_upload', "Bulk upload {$type}: inserted {$inserted} / processed {$processed}", $_SESSION['uid'] ?? null);
                    }
                } elseif ($error === '') {
                    $error = 'No records were inserted.';
                }

                if (!empty($errors)) {
                    $csv = fopen('php://temp', 'w+');
                    fputcsv($csv, ['row', 'error', 'data']);
                    foreach ($errors as $errRow) {
                        $dataString = is_array($errRow['data']) ? implode(' | ', $errRow['data']) : (string)$errRow['data'];
                        fputcsv($csv, [$errRow['row'], $errRow['error'], $dataString]);
                    }
                    rewind($csv);
                    $_SESSION['bulk_upload_errors_csv'] = stream_get_contents($csv);
                    fclose($csv);
                } else {
                    $_SESSION['bulk_upload_errors_csv'] = '';
                }
            }
        }
    }
}
?>

<?php include "../includes/sidebar_helper.php"; render_sidebar(); ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@200..800&display=swap');

* {
    box-sizing: border-box;
    font-family: "Sora", sans-serif;
}

body {
    background: linear-gradient(180deg, #f3f6ff 0%, #eff3f8 40%, #f7f9fc 100%);
    color: #1f2937;
}

.main-content {
    margin-left: 16rem;
    padding: 2rem 2.5rem 2rem 2rem;
    transition: margin .2s ease;
}

.dashboard-shell {
    position: relative;
    background: radial-gradient(1200px 400px at 20% -10%, rgba(30, 64, 175, 0.12), transparent 60%),
        radial-gradient(800px 300px at 90% 10%, rgba(14, 116, 144, 0.12), transparent 60%);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #1d4ed8, #0ea5a4);
    border: none;
    color: white;
    padding: 0.6rem 1.4rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.12s ease;
    text-decoration: none;
    display: inline-block;
    box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
}

.btn-primary-custom:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(29, 78, 216, 0.3);
}

.form-control,
.form-select {
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.45);
}

.help-card {
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 16px;
    padding: 1rem 1.25rem;
}

.error-table {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    overflow: hidden;
}

.error-table table {
    width: 100%;
    border-collapse: collapse;
}

.error-table th,
.error-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 0.9rem;
}

.error-table th {
    background-color: #f8fafc;
    font-weight: 600;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 1.25rem;
        padding-top: 3.5rem;
    }
}
</style>

<div class="main-content">
    <div class="dashboard-shell">
        <div class="page-header">
            <div>
                <h3>Bulk Upload</h3>
                <p class="text-muted mb-0">Upload CSV files for users, employees, leaves, payroll, and projects.</p>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="row g-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="bulk_upload" value="1">

            <div class="col-md-4">
                <label class="form-label">Data Type</label>
                <select name="data_type" class="form-select" required>
                    <option value="">Select type</option>
                    <option value="users">Users</option>
                    <option value="employees">Employees</option>
                    <option value="leave_requests">Leave Requests</option>
                    <option value="payroll_inputs">Payroll Inputs</option>
                    <option value="projects">Projects</option>
                    <option value="tasks">Tasks</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn-primary-custom w-100">Upload CSV</button>
            </div>
        </form>

        <div class="help-card mb-4">
            <h5 class="mb-2">CSV Templates</h5>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=users">Users</a>
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=employees">Employees</a>
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=leave_requests">Leave
                    Requests</a>
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=payroll_inputs">Payroll
                    Inputs</a>
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=projects">Projects</a>
                <a class="btn btn-outline-primary btn-sm" href="?download=template&type=tasks">Tasks</a>
                <?php if (!empty($_SESSION['bulk_upload_errors_csv'])): ?>
                <a class="btn btn-outline-danger btn-sm" href="?download=errors">Download Error Report</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="error-table">
            <table>
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errors as $err): ?>
                    <tr>
                        <td><?= htmlspecialchars($err['row']) ?></td>
                        <td><?= htmlspecialchars($err['error']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/sidebar_scripts.php"; ?>
