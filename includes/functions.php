<?php
/**
 * Global Helper Functions - RBI Engineering Suite
 */

/**
 * Sanitize user input for display (prevent XSS)
 */
function sanitize(mixed $input): string {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

/**
 * Shorthand alias for sanitize
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL and exit
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Set a flash message (one-time session message)
 */
function flash(string $message, string $type = 'info'): void {
    $_SESSION['flash_messages'][] = ['message' => $message, 'type' => $type];
}

/**
 * Get and clear all flash messages, returning rendered HTML
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as Bootstrap alerts
 */
function renderFlashMessages(): string {
    $messages = getFlashMessages();
    $html = '';
    foreach ($messages as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
               . e($f['message'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
               . '</div>';
    }
    return $html;
}

/**
 * Calculate age in years from a date string
 */
function calculateAge(string $date): float {
    $install = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($install);
    return $diff->y + ($diff->m / 12) + ($diff->d / 365.25);
}

/**
 * Format a date/datetime string for display
 */
function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return 'N/A';
    try { return (new DateTime($date))->format($format); }
    catch (Exception $e) { return 'N/A'; }
}

/**
 * Format a datetime with time component
 */
function formatDateTime(?string $date): string {
    return formatDate($date, 'M d, Y H:i');
}

/**
 * Format a number with thousand separators and decimal places
 */
function formatNumber($value, int $decimals = 2, string $prefix = ''): string {
    if ($value === null) return 'N/A';
    return $prefix . number_format((float)$value, $decimals);
}

/**
 * Return a CSS class / hex colour for a risk level
 */
function riskColor(string $riskLevel): array {
    return match (strtoupper($riskLevel)) {
        'VH' => ['class' => 'bg-danger-dark',  'hex' => '#721c24', 'text' => 'text-white', 'label' => 'Very High'],
        'H'  => ['class' => 'bg-danger',       'hex' => '#dc3545', 'text' => 'text-white', 'label' => 'High'],
        'MH' => ['class' => 'bg-warning-dark', 'hex' => '#fd7e14', 'text' => 'text-white', 'label' => 'Medium-High'],
        'M'  => ['class' => 'bg-warning',      'hex' => '#ffc107', 'text' => 'text-dark',  'label' => 'Medium'],
        'L'  => ['class' => 'bg-success',      'hex' => '#28a745', 'text' => 'text-white', 'label' => 'Low'],
        default => ['class' => 'bg-secondary',  'hex' => '#6c757d', 'text' => 'text-white', 'label' => $riskLevel],
    };
}

/**
 * Return a risk badge HTML string
 */
function riskBadge(string $level): string {
    $map = [
        'VH' => ['Very High', 'danger'], 'H' => ['High', 'danger'],
        'MH' => ['Medium-High', 'warning'], 'M' => ['Medium', 'info'], 'L' => ['Low', 'success'],
    ];
    $info = $map[$level] ?? ['Unknown', 'secondary'];
    return '<span class="badge bg-' . $info[1] . '">' . e($info[0]) . '</span>';
}

/**
 * Return a Bootstrap badge for a status string
 */
function statusBadge(string $status): string {
    $map = [
        'active' => 'success', 'in_service' => 'success', 'completed' => 'success',
        'compliant' => 'success', 'pending' => 'warning', 'due_soon' => 'warning',
        'in_progress' => 'info', 'overdue' => 'danger',
        'inactive' => 'secondary', 'decommissioned' => 'secondary',
        'out_of_service' => 'secondary', 'retired' => 'dark', 'cancelled' => 'dark',
    ];
    $class = $map[strtolower($status)] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
}

/**
 * Generate a CSRF token and store in session
 */
function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Aliases for CSRF
 */
function generateCsrfToken(): string {
    return csrfToken();
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Render a hidden CSRF input field
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

/**
 * Validate a submitted CSRF token
 */
function csrfValidate(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
        return false;
    }
    unset($_SESSION[CSRF_TOKEN_NAME]);
    return true;
}

/**
 * Return the current page name without extension
 */
function currentPage(): string {
    return basename($_SERVER['PHP_SELF'], '.php');
}

/**
 * Check if current URI contains a path segment
 */
function isActivePage(string $path): bool {
    return str_contains($_SERVER['REQUEST_URI'] ?? '', $path);
}

/**
 * Require authentication or redirect
 */
function requireAuth(): void {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        flash('Please log in to continue.', 'warning');
        redirect(BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Get current user's display name
 */
function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Get current user's role
 */
function currentUserRole(): string {
    return $_SESSION['auth_user_role'] ?? 'viewer';
}

/**
 * Build breadcrumb HTML from items array
 */
function breadcrumb(array $items): string {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
    $count = count($items);
    foreach ($items as $i => $item) {
        if ($i === $count - 1) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($item['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . e($item['url']) . '">' . e($item['label']) . '</a></li>';
        }
    }
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Return an asset-type icon (Font Awesome class)
 */
function assetIcon(string $type): string {
    return match ($type) {
        'vessel'          => 'fas fa-database',
        'tank'            => 'fas fa-oil-can',
        'piping'          => 'fas fa-project-diagram',
        'heat_exchanger'  => 'fas fa-exchange-alt',
        'column'          => 'fas fa-building',
        'reactor'         => 'fas fa-atom',
        'pump'            => 'fas fa-fan',
        'valve'           => 'fas fa-faucet',
        'boiler'          => 'fas fa-fire',
        default           => 'fas fa-cog',
    };
}

/**
 * Build pagination HTML
 */
function pagination(int $totalItems, int $currentPage, int $perPage, string $baseUrl): string {
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    if ($totalPages <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm justify-content-center">';

    $prevDisabled = ($currentPage <= 1) ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">&laquo;</a></li>';

    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    $nextDisabled = ($currentPage >= $totalPages) ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Truncate text to a maximum length
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Convert a risk level to a sortable numeric value
 */
function riskSortValue(string $level): int {
    return match (strtoupper($level)) {
        'VH' => 5, 'H' => 4, 'MH' => 3, 'M' => 2, 'L' => 1, default => 0,
    };
}

/**
 * Simple application logger
 */
function appLog(string $message, string $level = 'INFO'): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    @file_put_contents(LOGS_PATH . '/app.log', $line, FILE_APPEND);
}
