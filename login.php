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
    $submit_type = $_POST['submit_type'] ?? 'user'; // 'user' か 'admin'

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
                $user_role = (int)$user['role'];

                // 共通のセッション情報をセット
                $_SESSION['role'] = $user_role;
                $_SESSION['user_db_id'] = $user['id'];
                $_SESSION['login_user_id'] = $user['user_id'];

                if ($submit_type === 'admin') {
                    // 「管理者としてログイン」ボタンが押された場合
                    if ($user_role !== 1) {
                        // 一般ユーザーが管理者ボタンを押した場合は弾く
                        $error = "このIDには管理者権限がありません。";
                        // セットしたセッションをクリア
                        $_SESSION = [];
                    } else {
                        // 管理者権限があれば管理メニューへ
                        header('Location: admin_menu/admin_menu.php');
                        exit();
                    }
                } else {
                    // 「一般ユーザーとしてログイン」ボタンが押された場合
                    // 管理者であっても一般ユーザーであっても、そのまま一般画面（index.php）へ遷移
                    header('Location: index.php');
                    exit();
                }
            } else {
                $error = "ログイン情報が正しくありません。";
            }
        } catch (PDOException $e) {
            $error = "データベース接続に失敗しました。";
            error_log('ログインエラー: ' . $e->getMessage());
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
    <link rel="icon" href="favicon.jpg">

</head>
<body class="auth-page">
<header class="auth-header" style="display:flex; justify-content:center; align-items:center; flex-direction:column; margin-bottom: 40px;">
    <img src="logo.png" alt="YSE POS Logo" style="height: 80px; width: auto; margin-bottom: 20px;">
    <h1 style="margin: 0; font-size: 28px; margin-bottom: -20px;">YSE POS ログイン</h1>
</header>

    <main class="auth-container">
        <section class="auth-card">
            <?php if ($registered): ?>
                <div class="auth-notice success">登録が完了しました。<br>IDとパスワードでログインしてください。</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-notice error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" class="auth-form">
                <label>
                    ユーザーID / 管理者ID
                    <input type="text" name="user_id" placeholder="IDを入力" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>" autofocus>
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
