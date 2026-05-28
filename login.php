<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funcs/auth.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        destroySessionAndRedirect('login.php');
    }

    $error = 'ログアウト処理を確認できませんでした。もう一度操作してください。';
}

try {
    $pdo = getPdo();
    ensureAppSchema($pdo);

    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        header('Location: setup.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('setup check error: ' . $e->getMessage());
}

$registered = isset($_GET['registered']) && $_GET['registered'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'logout') {
    $userId = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $submitType = $_POST['submit_type'] ?? 'user';

    if ($userId === '' || $password === '') {
        $error = 'IDとパスワードを入力してください。';
    } else {
        try {
            $pdo = getPdo();
            ensureAppSchema($pdo);

            $stmt = $pdo->prepare('SELECT id, user_id, password_hash, role FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'ログイン情報が正しくありません。';
            } elseif ($submitType === 'admin' && (int)$user['role'] !== 1) {
                $error = 'このIDには管理者権限がありません。';
            } else {
                loginUser($user);

                if ($submitType === 'admin') {
                    header('Location: admin_menu/admin_menu.php?tab_login=1');
                    exit;
                }

                header('Location: index.php?tab_login=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'データベース接続に失敗しました。';
            error_log('login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSEレジシステム - ログイン</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <header class="auth-header" style="display:flex;justify-content:center;align-items:center;">
        <h1>YSE POS ログイン</h1>
    </header>

    <main class="auth-container">
        <section class="auth-card">
            <?php if ($registered): ?>
                <div class="auth-notice success">登録が完了しました。<br>IDとパスワードでログインしてください。</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-notice error"><?= h($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" class="auth-form">
                <label>
                    ユーザーID / 管理者ID
                    <input type="text" name="user_id" placeholder="ID" value="<?= h($_POST['user_id'] ?? '') ?>" autofocus>
                </label>
                <label>
                    パスワード
                    <input type="password" name="password" placeholder="パスワードを入力">
                </label>

                <div class="auth-actions">
                    <button type="submit" name="submit_type" value="user" class="auth-btn btn-user">一般ユーザーとしてログイン</button>
                    <button type="submit" name="submit_type" value="admin" class="auth-btn btn-admin">管理者としてログイン</button>
                </div>
            </form>

            <div class="auth-links">
                <a href="register.php">新規ユーザー登録はこちら</a>
            </div>
        </section>
    </main>
</body>
</html>
