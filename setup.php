<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funcs/auth.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$error = '';

try {
    $pdo = getPdo();
    ensureAppSchema($pdo);
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($userCount > 0) {
        header('Location: login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'セットアップ処理を確認できませんでした。もう一度操作してください。';
        } elseif ($password === '' || $passwordConfirm === '') {
            $error = 'パスワードを入力してください。';
        } elseif ($password !== $passwordConfirm) {
            $error = 'パスワードが一致しません。';
        } elseif (strlen($password) < 8) {
            $error = 'パスワードは8文字以上で入力してください。';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (user_id, password_hash, role, created_at) VALUES (?, ?, 1, NOW())');
            $stmt->execute(['adm', password_hash($password, PASSWORD_DEFAULT)]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
} catch (PDOException $e) {
    $error = 'データベース接続に失敗しました。XAMPPのMySQLを確認してください。';
    error_log('setup error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSE POS セットアップ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <header class="auth-header" style="display:flex;justify-content:center;align-items:center;">
        <h1>YSE POS セットアップ</h1>
    </header>

    <main class="auth-container">
        <section class="auth-card">
            <?php if ($error): ?>
                <div class="auth-notice error"><?= h($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <label>
                    管理者ID
                    <input type="text" value="adm" disabled>
                </label>
                <label>
                    管理者パスワード
                    <input type="password" name="password" placeholder="8文字以上" autofocus>
                </label>
                <label>
                    管理者パスワード（確認）
                    <input type="password" name="password_confirm" placeholder="もう一度入力">
                </label>

                <div class="auth-actions">
                    <button type="submit" class="auth-btn btn-admin">初期管理者を作成</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
