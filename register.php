<?php
// 接続関数を読み込み
require_once 'db.php'; // getPdoが定義されているファイル名

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($user_id === '' || $password === '') {
        $error = 'ユーザーIDとパスワードを入力してください。';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません。';
    } else {
        try {
            $pdo = getPdo();
            
            // 1. 同じユーザーIDが既に登録されていないかチェック
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'このユーザーIDは既に登録されています。';
            } else {
                // 2. 登録処理（パスワードは必ずハッシュ化）
                // 2. 登録処理
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // カラム名を password から password_hash に変更
                $stmt = $pdo->prepare("INSERT INTO users (user_id, password_hash) VALUES (?, ?)");
                $stmt->execute([$user_id, $hash]);


                // 登録完了後、ログイン画面へ
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'データベースエラーが発生しました。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>新規登録</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>新規登録</h1>

    <?php if ($error): ?>
        <p class="login-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" class="login-form">
        <dl>
            <dt>ユーザーID</dt>
            <dd>
                <input type="text" name="user_id"
                value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
            </dd>

            <dt>パスワード</dt>
            <dd>
                <input type="password" name="password">
            </dd>

            <dt>パスワード（確認）</dt>
            <dd>
                <input type="password" name="password_confirm">
            </dd>
        </dl>

        <div class="login-btn-wrap">
            <button class="btn-submit">登録する</button>
        </div>
    </form>

    <p class="login-lead">
        <a href="login.php">ログインに戻る</a>
    </p>
</div>

</body>
</html>