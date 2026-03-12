<?php
/**
 * Auth - Session-based authentication and authorization
 *
 * Handles login, logout, registration, role/permission checks.
 * Passwords are stored with password_hash (bcrypt by default).
 */
class Auth
{
    private Database $db;

    /** Session key that holds the authenticated user id */
    private const SESSION_USER_KEY = 'auth_user_id';
    private const SESSION_ROLE_KEY = 'auth_user_role';

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── Authentication ──────────────────────────────────────────────

    /**
     * Attempt to log in with email/username and password.
     *
     * @return array|false  User row on success, false on failure
     */
    public function login(string $email, string $password): array|false
    {
        try {
            $user = $this->db->query(
                "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1",
                [$email]
            )->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->logAuthEvent($email, 'login_failed');
                return false;
            }

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION[self::SESSION_USER_KEY] = $user['id'];
            $_SESSION[self::SESSION_ROLE_KEY] = $user['role'];
            $_SESSION['user_name']            = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email']           = $user['email'];
            $_SESSION['login_time']           = time();

            // Update last login timestamp
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

            $this->logAuthEvent($email, 'login_success');
            return $user;
        } catch (\Throwable $e) {
            error_log('[RBI Auth] Login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Destroy session and log out
     */
    public function logout(): void
    {
        $email = $_SESSION['user_email'] ?? 'unknown';
        $this->logAuthEvent($email, 'logout');

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Register a new user account
     *
     * @return int  New user ID
     */
    public function register(array $data): int
    {
        // Validate required fields
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        if (strlen($data['password']) < PASSWORD_MIN_LEN) {
            throw new InvalidArgumentException('Password must be at least ' . PASSWORD_MIN_LEN . ' characters.');
        }

        // Check uniqueness
        $existing = $this->db->query(
            "SELECT id FROM users WHERE email = ? LIMIT 1",
            [$data['email']]
        )->fetch();

        if ($existing) {
            throw new RuntimeException('A user with that email already exists.');
        }

        $userId = $this->db->insert('users', [
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'role'          => $data['role'] ?? 'inspector',
            'department'    => $data['department'] ?? null,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->logAuthEvent($data['email'], 'register');
        return $userId;
    }

    // ── Session Checks ──────────────────────────────────────────────

    /**
     * Is the current session authenticated?
     */
    public function isLoggedIn(): bool
    {
        if (empty($_SESSION[self::SESSION_USER_KEY])) {
            return false;
        }

        // Optional: enforce session lifetime
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Get the currently authenticated user record (cached per request)
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->db->find('users', (int) $_SESSION[self::SESSION_USER_KEY]);
    }

    /**
     * Get the current user's ID
     */
    public function getUserId(): ?int
    {
        return $this->isLoggedIn() ? (int) $_SESSION[self::SESSION_USER_KEY] : null;
    }

    // ── Authorization ───────────────────────────────────────────────

    /**
     * Role hierarchy (higher index = more privileges)
     */
    private const ROLE_HIERARCHY = [
        'viewer'     => 1,
        'inspector'  => 2,
        'engineer'   => 3,
        'manager'    => 4,
        'admin'      => 5,
    ];

    /**
     * Permission map: permission_name => minimum role required
     */
    private const PERMISSIONS = [
        'view_dashboard'          => 'viewer',
        'view_assets'             => 'viewer',
        'edit_assets'             => 'engineer',
        'delete_assets'           => 'admin',
        'view_assessments'        => 'viewer',
        'create_assessment'       => 'inspector',
        'approve_assessment'      => 'engineer',
        'view_inspection_plans'   => 'viewer',
        'create_inspection_plan'  => 'inspector',
        'manage_inspection_plans' => 'engineer',
        'view_reports'            => 'viewer',
        'generate_reports'        => 'inspector',
        'manage_users'            => 'admin',
        'manage_settings'         => 'admin',
        'view_audit_log'          => 'manager',
        'manage_integrations'     => 'admin',
    ];

    /**
     * Check whether the current user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION[self::SESSION_ROLE_KEY] ?? 'viewer';

        if (!isset(self::PERMISSIONS[$permission])) {
            return false; // unknown permission defaults to deny
        }

        $requiredRole = self::PERMISSIONS[$permission];
        return $this->roleLevel($userRole) >= $this->roleLevel($requiredRole);
    }

    /**
     * Assert that the current user has at least the given role
     */
    public function checkRole(string $minimumRole): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $userRole = $_SESSION[self::SESSION_ROLE_KEY] ?? 'viewer';
        return $this->roleLevel($userRole) >= $this->roleLevel($minimumRole);
    }

    /**
     * Require authentication; redirect to login if not authenticated
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            flash('Please log in to continue.', 'warning');
            redirect(BASE_URL . '/login.php');
            exit;
        }
    }

    /**
     * Require a minimum role; show 403 if insufficient
     */
    public function requireRole(string $minimumRole): void
    {
        $this->requireLogin();
        if (!$this->checkRole($minimumRole)) {
            http_response_code(403);
            flash('Insufficient permissions.', 'danger');
            redirect(BASE_URL . '/index.php');
            exit;
        }
    }

    // ── Password Management ─────────────────────────────────────────

    /**
     * Change password for a user
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->db->find('users', $userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        if (strlen($newPassword) < PASSWORD_MIN_LEN) {
            throw new InvalidArgumentException('Password must be at least ' . PASSWORD_MIN_LEN . ' characters.');
        }

        $this->db->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [$userId]);

        return true;
    }

    // ── Internals ───────────────────────────────────────────────────

    private function roleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }

    private function logAuthEvent(string $identifier, string $event): void
    {
        try {
            $this->db->insert('audit_log', [
                'user_identifier' => $identifier,
                'event'           => $event,
                'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[RBI Auth] Audit log write failed: ' . $e->getMessage());
        }
    }
}
