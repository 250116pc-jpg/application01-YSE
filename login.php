
<?php
require_once 'db.php'; // getPdo() が定義されているファイル

// 1. 変数の初期化（これでWarningが消えます）
$error = '';
$registered = isset($_GET['registered']); // register.phpからリダイレクト時に ?registered=1 を付ける想定

// 2. ログインボタンが押された時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? null; // ラジオボタンの値

    if ($user_id === '' || $password === '' || $role === null) {
        $error = 'ユーザーID、パスワード、権限を選択してください。';
    } else {
        try {
            $pdo = getPdo();
            
            // ユーザーを取得
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            // ユーザーが存在し、パスワードが一致し、権限も一致するかチェック
            if ($user && password_verify($password, $user['password_hash'])) {
                
                if ((int)$user['role'] === (int)$role) {
                    // ログイン成功！セッションに保存してトップページなどへ
                    session_start();
                    $_SESSION['user'] = $user;
                    
                    // 権限によって遷移先を分ける例
                    if ($role === '1') {
                        header('Location: admin_menu.php'); // 管理者用
                    } else {
                        header('Location: index.php'); // 一般ユーザー用
                    }
                    exit;
                } else {
                    $error = '選択された権限が正しくありません。';
                }
            } else {
                $error = 'ユーザーIDまたはパスワードが間違っています。';
            }
        } catch (PDOException $e) {
            $error = 'データベースエラーが発生しました。';
        }
    }
}
?>
<!-- ここから下に、提示いただいた HTML を続けます -->

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>ログイン</h1>

    <?php if ($registered): ?>
        <p class="login-error" style="color: green;">
            登録が完了しました
        </p>
    <?php endif; ?>

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
        </dl>

        <div class="checkbox-wrap">
            <label>
                <input type="radio" name="role" value="0"> ユーザ利用
            </label>
            <br>
            <label>
                <input type="radio" name="role" value="1"> 管理者
            </label>
        </div>

        <div class="login-btn-wrap">
            <button class="btn-submit">ログイン</button>
        </div>
    </form>

    <p class="login-lead">
        <a href="register.php">新規登録はこちら</a>
    </p>
</div>

</body>
</html>