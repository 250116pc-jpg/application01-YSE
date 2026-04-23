<?php
session_start();

$error = []; 

// 既にログインしている場合は、権限に応じてリダイレクト
if (isset($_SESSION['id'])) {
    if ($_SESSION['role'] == 1) {
        header('Location: admin_menu.php'); // 管理者メニューへ
    } else {
        header('Location: index.php'); // 一般ユーザー（レジ）へ
    }
    exit();
}

if(!empty($_POST)){
    if($_POST['email'] != '' && $_POST['password'] != ''){
        // データベースからユーザー情報を取得（roleも取得する）
        /* ここはDB接続のコードが入ります
        $login = $db->prepare('SELECT id, role FROM members WHERE email=? AND password=?');
        ...
        */
    }else{
        $error['login'] = 'blank';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>YSEレジシステム - ログイン</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>YSEレジシステム</h1>
    
    <div class="login-lead">
        <p>ログインしてください。</p>
        <p>※会員登録は<a href="join/index.php">こちら</a></p>
    </div>
    
    <?php if (isset($error['login']) && $error['login'] === 'failed'): ?>
        <p class="login-error">※メールアドレスかパスワードが間違っています。</p>
    <?php endif; ?>
    <?php if (isset($error['login']) && $error['login'] === 'blank'): ?>
        <p class="login-error">※メールアドレスとパスワードを入力してください。</p>
    <?php endif; ?>

    <form action="" method="post" class="login-form">
        <dl>
            <dt>メールアドレス</dt>
            <dd>
                <input type="text" name="email" maxlength="255" value="<?= htmlspecialchars(($_POST['email'] ?? ($_COOKIE['email'] ?? '')), ENT_QUOTES) ?>">
            </dd>
            
            <dt>パスワード</dt>
            <dd>
                <input type="password" name="password" id="password" maxlength="255" value="<?= htmlspecialchars(($_POST['password'] ?? ($_COOKIE['password'] ?? '')), ENT_QUOTES) ?>">
                <div class="checkbox-wrap">
                    <label>
                        <input type="checkbox" id="show_pass"> パスワードを表示する
                    </label>
                </div>
            </dd>
            
            <dt>ログイン情報の保持</dt>
            <dd>
                <div class="checkbox-wrap">
                    <label>
                        <input id="save" type="checkbox" name="save" value="on"> 次回から自動的にログインする
                    </label>
                </div>
            </dd>
        </dl>
        <div class="login-btn-wrap">
            <input type="submit" value="ログイン" class="btn-submit">
        </div>
    </form>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const showPassCheckbox = document.getElementById('show_pass');

    showPassCheckbox.addEventListener('change', function() {
        if (this.checked) {
            passwordInput.setAttribute('type', 'text');
        } else {
            passwordInput.setAttribute('type', 'password');
        }
    });
</script>
</body>
</html>
