<?php
const SESSION_TTL = 3600;
const ADMIN_SESSION_TTL = 3600;

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function loginUser(array $user)
{
    session_regenerate_id(true);
    $_SESSION['role'] = (int)$user['role'];
    $_SESSION['user_db_id'] = (int)$user['id'];
    $_SESSION['login_user_id'] = $user['user_id'];
    $_SESSION['time'] = time();
    csrfToken();
}

function destroySessionAndRedirect($location)
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: ' . $location);
    exit;
}

function isSessionActive(): bool
{
    return isset($_SESSION['user_db_id'])
        && isset($_SESSION['time'])
        && $_SESSION['time'] + SESSION_TTL > time();
}

function requireLogin($loginLocation = 'login.php')
{
    if (!isSessionActive()) {
        destroySessionAndRedirect($loginLocation);
    }

    $_SESSION['time'] = time();
}

function requireAdmin($loginLocation = 'login.php')
{
    $isAdmin = isSessionActive()
        && (int)($_SESSION['role'] ?? -1) === 1;

    $isFresh = isset($_SESSION['time']) && $_SESSION['time'] + ADMIN_SESSION_TTL > time();

    if (!$isAdmin || !$isFresh) {
        destroySessionAndRedirect($loginLocation);
    }

    $_SESSION['time'] = time();
}

function renderTabSessionGuard($logoutPath = 'login.php')
{
    $path = htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <script>
        (function () {
            const tabKey = 'yse_pos_tab_login';
            const params = new URLSearchParams(window.location.search);

            if (params.get('tab_login') === '1') {
                sessionStorage.setItem(tabKey, '1');
                params.delete('tab_login');
                const query = params.toString();
                const cleanUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
                window.history.replaceState({}, document.title, cleanUrl);
                return;
            }

            if (!sessionStorage.getItem(tabKey)) {
                window.location.href = '{$path}?logout=1';
            }
        })();
    </script>
HTML;
}
?>
