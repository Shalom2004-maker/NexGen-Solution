<?php
// Database migration helper for NexGen-Solution
// Usage: php scripts/migrate_db.php

require_once __DIR__ . "/../includes/db.php";

function dd($msg)
{
    echo $msg . PHP_EOL;
}

$conn->autocommit(false);
try {
    dd("Starting DB migration...");

    // 1) Create roles table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql) or throw new Exception($conn->error);
    dd("Ensured 'roles' table exists.");

    // 2) Seed roles
    $roles = ['Employee', 'ProjectLeader', 'HR', 'Admin'];
    $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?) ON DUPLICATE KEY UPDATE role_name=role_name");
    foreach ($roles as $r) {
        $stmt->bind_param('s', $r);
        $stmt->execute() or throw new Exception($stmt->error);
    }
    $stmt->close();
    dd("Seeded roles: " . implode(',', $roles));

    // 3) Ensure users.role_id column exists
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() AS db")->fetch_object()->db);
    $colCheck = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role_id'")->fetch_object()->c;
    if ($colCheck == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role_id INT NULL DEFAULT NULL") or throw new Exception($conn->error);
        dd("Added 'role_id' column to 'users' table.");
    } else {
        dd("'users.role_id' already exists.");
    }

    // 4) Clean invalid role_id references (set to NULL where no matching role)
    $conn->query("UPDATE users SET role_id = NULL WHERE role_id IS NOT NULL AND role_id NOT IN (SELECT id FROM roles)") or throw new Exception($conn->error);
    dd("Cleared invalid role_id references.");

    // 5) Add foreign key constraint if not exists
    $fkExists = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role_id' AND REFERENCED_TABLE_NAME='roles'")->fetch_object()->c;
    if ($fkExists == 0) {
        // Ensure index exists on role_id
        $idxExists = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role_id'")->fetch_object()->c;
        if ($idxExists == 0) {
            $conn->query("ALTER TABLE users ADD INDEX idx_users_role_id (role_id)") or throw new Exception($conn->error);
            dd("Added index idx_users_role_id.");
        }
        $conn->query("ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE") or throw new Exception($conn->error);
        dd("Added foreign key fk_users_role.");
    } else {
        dd("Foreign key from users.role_id to roles.id already exists.");
    }

    // 6) Add UNIQUE index on users.email if no duplicates
    $dupRes = $conn->query("SELECT email, COUNT(*) c FROM users GROUP BY email HAVING c>1");
    if ($dupRes && $dupRes->num_rows > 0) {
        dd("Found duplicate emails in users table; cannot add UNIQUE constraint. Duplicates listed below:");
        while ($r = $dupRes->fetch_assoc()) {
            dd(" - {$r['email']} ({$r['c']})");
        }
        dd("Resolve duplicates manually, then re-run this migration to add the UNIQUE index.");
    } else {
        // check if index exists
        $idxRes = $conn->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='idx_users_email'")->fetch_object()->c;
        if ($idxRes == 0) {
            $conn->query("CREATE UNIQUE INDEX idx_users_email ON users(email)") or throw new Exception($conn->error);
            dd("Created UNIQUE index idx_users_email on users(email).");
        } else {
            dd("Unique index idx_users_email already exists.");
        }
    }

    $conn->commit();
    dd("Migration completed successfully.");
} catch (Exception $e) {
    $conn->rollback();
    dd("Migration failed: " . $e->getMessage());
    exit(1);
}
