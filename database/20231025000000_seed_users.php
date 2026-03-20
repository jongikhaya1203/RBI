<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedUsers extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        // Generate a secure bcrypt hash for the default password 'demo123'
        $passwordHash = password_hash('demo123', PASSWORD_DEFAULT);

        // Role IDs map to schema.sql: 2=Engineer, 3=Inspector, 4=Viewer
        $users = [
            [
                'username'      => 'john.engineer',
                'email'         => 'john.engineer@petrotech.com',
                'password_hash' => $passwordHash,
                'first_name'    => 'John',
                'last_name'     => 'Martinez',
                'job_title'     => 'Senior RBI Engineer',
                'department'    => 'Asset Integrity',
                'role_id'       => 2,
                'status'        => 'active',
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'username'      => 'sarah.inspector',
                'email'         => 'sarah.inspector@petrotech.com',
                'password_hash' => $passwordHash,
                'first_name'    => 'Sarah',
                'last_name'     => 'Chen',
                'job_title'     => 'Lead Inspector',
                'department'    => 'Inspection Services',
                'role_id'       => 3,
                'status'        => 'active',
                'created_at'    => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('users')->insert($users)->saveData();
    }

    /**
     * Migrate Down (Rollback).
     */
    public function down(): void
    {
        $this->execute("DELETE FROM users WHERE username IN ('john.engineer', 'sarah.inspector')");
    }
}