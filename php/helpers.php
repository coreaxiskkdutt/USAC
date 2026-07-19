<?php
require_once __DIR__ . '/db.php';

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    return $user ?: null;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(array $user): bool {
    return $user['role'] === 'admin';
}

function isSafetyOfficer(array $user): bool {
    return $user['role'] === 'safety_officer';
}

function canManageUsers(array $user): bool {
    return in_array($user['role'], ['admin', 'safety_officer']);
}

function canEditReports(array $user): bool {
    return in_array($user['role'], ['admin', 'safety_officer']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash'] = ['Please log in to access this page.', 'warning'];
        header('Location: index.php');
        exit;
    }
}

function requireSafetyOfficer(): void {
    requireLogin();
    $user = currentUser();
    if (!canManageUsers($user)) {
        $_SESSION['flash'] = ['Access denied. Safety Officer or Admin role required.', 'danger'];
        header('Location: dashboard.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    $user = currentUser();
    if (!isAdmin($user)) {
        $_SESSION['flash'] = ['Access denied. Admin role required.', 'danger'];
        header('Location: dashboard.php');
        exit;
    }
}

function flash(string $message, string $category = 'info'): void {
    $_SESSION['flash'] = [$message, $category];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function generateReportNumber(): string {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->querySingle("SELECT COUNT(*) FROM reports WHERE report_number LIKE 'USAC-{$year}-%'");
    return sprintf('USAC-%s-%04d', $year, $stmt + 1);
}

function allowedFile(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

function timeAgo(string $datetime): string {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}

function statusClass(string $status): string {
    if ($status === 'Open') return 'status-open';
    if ($status === 'Closed') return 'status-closed';
    return 'status-progress';
}

function sevClass(string $sev): string {
    return 'sev-' . strtolower($sev);
}

function typeClass(string $type): string {
    return $type === 'Unsafe Act' ? 'act' : 'cond';
}
