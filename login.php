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
        $login = $db->prepare('SELECT id, role FROM members WHERE email=? AND password=?');
        $login->execute(array(
            $_POST['email'],
            sha1($_POST['password']) // パスワードはsha1でハッシュ化されている前提
        ));
        $member = $login->fetch();

        if($member){
            // ログイン成功：セッションにIDと権限（role）を保存
            $_SESSION['id'] = $member['id'];
            $_SESSION['role'] = $member['role']; // ここが重要：権限を保存
            $_SESSION['time'] = time();

            // クッキーへの保存処理（元のコードのまま）
            if(isset($_POST['save']) && $_POST['save'] === 'on'){
                setcookie('email', $_POST['email'], time() + 60 * 60 * 24 * 14);
                // パスワードをクッキーに生で保存するのはセキュリティ上非推奨ですが、元のコードを尊重します
                setcookie('password', $_POST['password'], time() + 60 * 60 * 24 * 14);
            }

            // 【ここが核心：画像遷移図の仕組み】
            // 権限（role）に応じて遷移先を切り替える
            if ($member['role'] == 1) {
                // 管理者の場合
                header('Location: admin_menu.php'); 
            } else {
                // 一般ユーザーの場合（role = 0 またはそれ以外）
                header('Location: index.php'); // レジ画面へ
            }
            exit();
        }else{
            $error['login'] = 'failed';
        }
    }else{
        $error['login'] = 'blank';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>YSEレジシステム - ログイン</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ログイン画面用のスタイル（元のコードをベースに微調整） */
        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            background-color: #34495e; /* レジ画面と統一感のある背景色 */
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        #wrap {
            background: #dcdde1; /* レジマシンの色 */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            max-width: 400px;
            width: 100%;
            border-bottom: 10px solid #7f8c8d; /* レジマシンのデザインを踏襲 */
        }

        h1 {
            text-align: center;
            color: #2f3640;
            margin-top: 0;
            font-size: 32px;
        }

        #lead p { text-align: center; color: #7f8c8d; }
        #lead p a { color: #3498db; text-decoration: none; }
        #lead p a:hover { text-decoration: underline; }

        /* エラー文字のスタイル */
        .error { color: #e84118; font-size: 14px; margin-top: 5px; font-weight: bold; text-align: center; }
        
        /* 入力欄の配置設定 */
        dl { margin-top: 30px; }
        dt { font-weight: bold; color: #2f3640; margin-top: 15px; }
        dd { margin-left: 0; margin-top: 5px; }

        input[type="text"],
        input[type="password"] {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
            background: #f5f6fa;
        }

        .password-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .show-pass-label, .save-label {
            font-size: 13px;
            margin-top: 8px;
            cursor: pointer;
            user-select: none;
            color: #7f8c8d;
            display: flex;
            align-items: center;
        }
        
        .show-pass-label input, .save-label input { margin-right: 5px; }

        /* ログインボタンのスタイル（レジのボタン風に） */
        .submit-group { text-align: center; margin-top: 30px; }
        input[type="submit"] {
            background: #27ae60; /* 確定ボタンの色 */
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px #219150;
            transition: all 0.1s;
        }
        input[type="submit"]:active {
            transform: translateY(2px);
            box-shadow: 0 2px #219150;
        }
    </style>
</head>
<body>
<div id="content">
    <div id="wrap"> 

    <div id="lead">
        <h1>YSEレジシステム</h1>
        <p>ログインしてください。</p>
        <p>※会員登録は<a href="join/index.php">こちら</a></p>
    </div>
    
    <?php if (isset($error['login']) && $error['login'] === 'failed'): ?>
        <p class="error">※メールアドレスかパスワードが間違っています。</p>
    <?php endif; ?>
    <?php if (isset($error['login']) && $error['login'] === 'blank'): ?>
        <p class="error">※メールアドレスとパスワードを入力してください。</p>
    <?php endif; ?>

    <form action="" method="post">
        <dl>
            <dt>メールアドレス</dt>
            <dd>
                <input type="text" name="email" maxlength="255" value="<?= htmlspecialchars(($_POST['email'] ?? ($_COOKIE['email'] ?? '')), ENT_QUOTES) ?>">
            </dd>
            
            <dt>パスワード</dt>
            <dd class="password-group">
                <input type="password" name="password" id="password" maxlength="255" value="<?= htmlspecialchars(($_POST['password'] ?? ($_COOKIE['password'] ?? '')), ENT_QUOTES) ?>">
                
                <label class="show-pass-label">
                    <input type="checkbox" id="show_pass"> パスワードを表示する
                </label>
            </dd>
            
            <dt>ログイン情報の保持</dt>
            <dd>
                <label class="save-label" for="save">
                    <input id="save" type="checkbox" name="save" value="on"> 次回から自動的にログインする
                </label>
            </dd>
        </dl>
        <div class="submit-group">
            <input type="submit" value="ログイン">
        </div>
    </form>
    </div>
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
