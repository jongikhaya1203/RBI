<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=rbi_engineering;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $demoHash = password_hash('demo123', PASSWORD_DEFAULT);

    // Fix existing admin user (id=1)
    $pdo->prepare("UPDATE users SET email = ?, password_hash = ?, status = 'active', first_name = 'System', last_name = 'Admin' WHERE id = 1")
        ->execute(['admin@rbi-suite.com', $adminHash]);
    echo "Updated admin user: admin@rbi-suite.com / admin123\n";

    // Create demo users
    $users = [
        ['john.engineer', 'John', 'Engineer', 'john.engineer@petrotech.com', 2],
        ['sarah.inspector', 'Sarah', 'Inspector', 'sarah.inspector@petrotech.com', 3],
        ['mike.manager', 'Mike', 'Manager', 'mike.manager@petrotech.com', 2],
        ['lisa.viewer', 'Lisa', 'Viewer', 'lisa.viewer@petrotech.com', 4],
    ];

    foreach ($users as $u) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$u[3]]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')")
                ->execute([$u[0], $u[1], $u[2], $u[3], $demoHash, $u[4]]);
            echo "Created: {$u[3]}\n";
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE email = ?")
                ->execute([$demoHash, $u[3]]);
            echo "Updated: {$u[3]}\n";
        }
    }

    // Verify
    echo "\n=== ALL USERS ===\n";
    $all = $pdo->query("SELECT u.id, u.email, u.status, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id")->fetchAll();
    foreach ($all as $row) {
        echo "  [{$row['id']}] {$row['email']} | {$row['role_name']} | {$row['status']}\n";
    }
    echo "\nLogin ready: admin@rbi-suite.com / admin123\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
