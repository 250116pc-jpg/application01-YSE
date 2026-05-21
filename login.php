<?php
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit();
}

$error = "";
$registered = isset($_GET['registered']) && $_GET['registered'] == 1;

if (!empty($_POST)) {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($user_id === "" || $password === "") {
        $error = "IDとパスワードを入力してください。";
    } else {
        require_once 'db.php';

        try {
            $pdo = getPdo();
            ensureAppSchema($pdo);
            ensureDefaultAdmin($pdo);

            $stmt = $pdo->prepare('SELECT id, user_id, password_hash, role FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['role'] = (int)$user['role'];
                $_SESSION['user_db_id'] = $user['id'];
                $_SESSION['login_user_id'] = $user['user_id'];

                if ((int)$user['role'] === 1) {
                    header('Location: admin_menu.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = "ログイン情報が正しくありません";
            }
        } catch (PDOException $e) {
            $error = "データベース接続に失敗しました。管理者に連絡してください";
            error_log('ログインエラー: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSEレジシステム ログイン</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow">YSE POS</p>
            <h1>ログイン</h1>

            <?php if ($registered): ?>
                <div class="notice success auth-notice">登録が完了しました。IDとパスワードでログインしてください。</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="notice error auth-notice"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" class="auth-form">
                <label>
                    ユーザーID
                    <input type="text" name="user_id" placeholder="ユーザーID" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" autofocus>
                </label>
                <label>
                    パスワード
                    <input type="password" name="password" placeholder="パスワード">
                </label>
                <button type="submit" class="primary-btn">ログイン</button>
            </form>

            <div class="auth-links">
                <a class="button-link" href="register.php">新規登録</a>
                <a class="button-link" href="index.php">レジへ戻る</a>
            </div>
        </section>
    </main>
</body>
</html>
