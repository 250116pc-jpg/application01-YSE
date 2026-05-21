<?php
session_start();
$error = "";

if (!empty($_POST)) {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($user_id === "" || $password === "" || $password_confirm === "") {
        $error = "IDとパスワードをすべて入力してください。";
    } elseif ($password !== $password_confirm) {
        $error = "パスワードが一致しません。";
    } elseif (strtolower($user_id) === 'adm') {
        $error = "admは管理者専用IDです。別のIDを入力してください。";
    } elseif (!preg_match('/^[a-zA-Z0-9_@.-]{3,20}$/', $user_id)) {
        $error = "IDは英数字、_ @ . - の3～20文字で入力してください。";
    } else {
        require_once 'db.php';
        try {
            $pdo = getPdo();
            ensureAppSchema($pdo);
            ensureDefaultAdmin($pdo);

            $stmt = $pdo->prepare('SELECT id FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $error = "そのIDはすでに使われています。別のIDを入力してください。";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (user_id, password_hash, role, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$user_id, $password_hash, 0]);

                header('Location: login.php?registered=1');
                exit();
            }
        } catch (PDOException $e) {
            $error = "データベース接続に失敗しました。管理者に連絡してください。";
            error_log('登録エラー: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSEレジシステム - 新規登録</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <header class="auth-header">
        <h1>YSE POS 新規ユーザー登録</h1>
    </header>

    <main class="auth-container">
        <section class="auth-card">
            <?php if ($error): ?>
                <div class="auth-notice error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" class="auth-form">
                <label>
                    ユーザーID
                    <input type="text" name="user_id" placeholder="半角英数字3～20文字" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" autofocus>
                </label>
                <label>
                    パスワード
                    <input type="password" name="password" placeholder="パスワードを入力">
                </label>
                <label>
                    パスワード（確認）
                    <input type="password" name="password_confirm" placeholder="もう一度入力">
                </label>
                
                <div class="auth-actions">
                    <button type="submit" class="auth-btn btn-user">登録する</button>
                </div>
            </form>

            <div class="auth-links">
                <a href="login.php">ログイン画面へ戻る</a>
            </div>
        </section>
    </main>
</body>
</html>