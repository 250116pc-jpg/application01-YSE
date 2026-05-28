<?php
session_start();
require_once 'db.php';
require_once 'funcs/auth.php';

// ログアウト処理
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit();
}

$error = "";
$registered = isset($_GET['registered']) && $_GET['registered'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $submit_type = $_POST['submit_type'] ?? 'user';

    if ($user_id === "" || $password === "") {
        $error = "IDとパスワードを入力してください。";
    } else {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('SELECT id, user_id, password_hash, role FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // 正しいログインセッションを初期化（タイムスタンプとCSRFトークン含む）
                loginUser($user);

                if ($submit_type === 'admin') {
                    if ((int)$user['role'] === 1) {
                        header('Location: admin_menu/admin_menu.php');
                        exit();
                    } else {
                        $error = "このIDには管理者権限がありません。";
                        $_SESSION = [];
                    }
                } else {
                    header('Location: index.php');
                    exit();
                }
            } else {
                $error = "IDまたはパスワードが正しくありません。";
            }
        } catch (PDOException $e) {
            $error = "データベースエラーが発生しました。";
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
    <link rel="icon" type="image/jpeg" href="favicon.jpg">
</head>
<body class="auth-page">
    <header class="auth-header" style="display:flex; justify-content:center; align-items:center; flex-direction:column; margin-bottom: 40px;">
        <img src="logo.png" alt="Logo" style="height: 80px; width: auto; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 28px;">YSE POS ログイン</h1>
    </header>

    <main class="auth-container">
        <section class="auth-card">
            <?php if ($registered): ?>
                <div class="auth-notice success">登録が完了しました。ログインしてください。</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form action="login.php" method="post" class="auth-form">
                <label>
                    ユーザーID
                    <input type="text" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </label>
                <label>
                    パスワード
                    <input type="password" name="password" required>
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
