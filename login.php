<?php
session_start();
// ※本来はここに $db（DB接続）の読み込みが必要です
// include('dbconnect.php'); 

$error = []; 

if (isset($_SESSION['id'])) {
    if ($_SESSION['role'] == 1) {
        header('Location: admin_menu.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

if(!empty($_POST)){
    if($_POST['email'] != '' && $_POST['password'] != ''){
        // ログイン処理（DB接続がある前提）
        /*
        $login = $db->prepare('SELECT id, role FROM members WHERE email=? AND password=?');
        $login->execute(array($_POST['email'], sha1($_POST['password'])));
        $member = $login->fetch();
        // ...以下、以前のロジックと同じ
        */
    } else {
        $error['login'] = 'blank';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSEレジ - ログイン</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>YSEレジシステム</h1>
    
    <div class="login-info">
        <p>ログインしてください</p>
        <p><a href="join/index.php">新規会員登録はこちら</a></p>
    </div>
    
    <?php if (isset($error['login'])): ?>
        <p class="error">※入力内容を確認してください</p>
    <?php endif; ?>

    <form action="" method="post">
        <dl>
            <dt>メールアドレス</dt>
            <dd>
                <input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
            </dd>
            
            <dt>パスワード</dt>
            <dd>
                <input type="password" name="password" id="password">
                <label class="checkbox-label">
                    <input type="checkbox" id="show_pass"> パスワードを表示
                </label>
            </dd>
            
            <dt>自動ログイン</dt>
            <dd>
                <label class="checkbox-label">
                    <input type="checkbox" name="save" value="on"> 次回から自動的にログイン
                </label>
            </dd>
        </dl>

        <div class="submit-group">
            <input type="submit" value="ログイン" class="btn-login">
        </div>
    </form>
</div>

<script>
    // パスワード表示切り替え
    const passwordInput = document.getElementById('password');
    const showPassCheckbox = document.getElementById('show_pass');

    showPassCheckbox.addEventListener('change', function() {
        passwordInput.type = this.checked ? 'text' : 'password';
    });
</script>
</body>
</html>
