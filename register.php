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
    } elseif (!preg_match('/^[a-zA-Z0-9_@.-]{3,20}$/', $user_id)) {
        $error = "IDは英数字、_ @ . - の3～20文字で入力してください。";
    } else {
        require_once 'db.php';

        try {
            $pdo = getPdo();

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
    <title>YSEレジシステム 新規登録</title>
    <style>
        body { background-color: #34495e; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #dcdde1; padding: 40px; border-radius: 15px; width: 700px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border-bottom: 10px solid #7f8c8d; }
        h1 { text-align: center; color: #2f3640; margin-top: 0; margin-bottom: 30px; }
        .input-side { display: flex; flex-direction: column; gap: 20px; }
        .input-side label { font-weight: bold; color: #2f3640; font-size: 14px; }
        .input-side input { width: 100%; padding: 15px; border-radius: 6px; border: 1px solid #bdc3c7; font-size: 16px; box-sizing: border-box; }
        .submit-container { text-align: center; margin-top: 20px; }
        .submit-btn { width: 80%; padding: 20px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 22px; font-weight: bold; cursor: pointer; box-shadow: 0 5px #219150; }
        .submit-btn:active { transform: translateY(3px); box-shadow: 0 2px #219150; }
        .link { margin-top: 15px; text-align: center; }
        .link a { color: #2f3640; text-decoration: none; font-weight: bold; }
        .error-msg { color: #e84118; font-weight: bold; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="login-box">
    <h1>YSEレジシステム 新規登録</h1>
    <?php if ($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <div class="input-side">
            <div>
                <label>ユーザーID</label>
                <input type="text" name="user_id" placeholder="登録するIDを入力" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
            </div>
            <div>
                <label>パスワード</label>
                <input type="password" name="password" placeholder="パスワードを入力">
            </div>
            <div>
                <label>パスワード（確認）</label>
                <input type="password" name="password_confirm" placeholder="もう一度パスワードを入力">
            </div>
        </div>

        <div class="submit-container">
            <button type="submit" class="submit-btn">登録する</button>
        </div>
    </form>

    <div class="link">
        <a href="login.php">ログイン画面に戻る</a>
    </div>
</div>
</body>
</html>
