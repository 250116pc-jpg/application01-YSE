<?php
session_start();
$error = "";
$registered = isset($_GET['registered']) && $_GET['registered'] == 1;

if (!empty($_POST)) {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // 0: ユーザ利用, 1: 管理者機能

    if ($user_id === "" || $password === "") {
        $error = "IDとパスワードを入力してください";
    } elseif ($role === "") {
        $error = "右側のボタンから「ユーザ利用」か「管理者機能」を選択してください";
    } else {
        require_once 'db.php';

        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ((int)$role !== (int)$user['role']) {
                    $error = "選択した機能とログイン権限が一致しません";
                } else {
                    $_SESSION['role'] = $role;
                    if ($role == 1) {
                        header('Location: admin_menu.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                }
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
    <style>
        body { background-color: #34495e; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #dcdde1; padding: 40px; border-radius: 15px; width: 700px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border-bottom: 10px solid #7f8c8d; }
        
        h1 { text-align: center; color: #2f3640; margin-top: 0; margin-bottom: 30px; }

        /* 左(入力)と右(選択)を並べるメインコンテナ */
        .main-layout { display: flex; gap: 30px; margin-bottom: 30px; }

        /* 左側：入力エリア */
        .input-side { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .input-side label { font-weight: bold; color: #2f3640; font-size: 14px; }
        .input-side input { 
            width: 100%; padding: 15px; border-radius: 6px; 
            border: 1px solid #bdc3c7; font-size: 16px; box-sizing: border-box;
        }

        /* 右側：選択エリア */
        .select-side { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .role-btn { 
            flex: 1; background: #fff; border: 3px solid #bdc3c7; border-radius: 10px; 
            cursor: pointer; font-weight: bold; font-size: 18px; color: #7f8c8d;
            display: flex; align-items: center; justify-content: center; transition: 0.2s;
        }
        /* 選択時の色設定（ユーザ：青、管理者：紫） */
        .role-btn.active-user { border-color: #3498db; color: #3498db; background: #ebf5fb; transform: scale(1.02); }
        .role-btn.active-admin { border-color: #9b59b6; color: #9b59b6; background: #f5eef8; transform: scale(1.02); }

        /* 下部：確定ボタン */
        .submit-container { text-align: center; }
        .submit-btn { 
            width: 80%; padding: 20px; background: #27ae60; color: white; 
            border: none; border-radius: 8px; font-size: 22px; font-weight: bold; 
            cursor: pointer; box-shadow: 0 5px #219150; 
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .submit-btn:active { transform: translateY(3px); box-shadow: 0 2px #219150; }

        .error-msg { color: #e84118; font-weight: bold; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="login-box">
    <h1>YSEレジシステム</h1>

    <?php if ($registered): ?>
        <p class="error-msg" style="color: #27ae60;">登録が完了しました。IDとパスワードでログインしてください。</p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post" id="loginForm">
        <input type="hidden" name="role" id="selectedRole" value="">

        <div class="main-layout">
            <div class="input-side">
                <div>
                    <label>ユーザーID</label>
                    <input type="text" name="user_id" placeholder="ユーザーIDを入力" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
                </div>
                <div>
                    <label>パスワード</label>
                    <input type="password" name="password" placeholder="パスワードを入力">
                </div>
            </div>

            <div class="select-side">
                <div class="role-btn" id="btnUser" onclick="selectRole(0)">
                    ユーザ利用<br>(どちらかを選択)
                </div>
                <div class="role-btn" id="btnAdmin" onclick="selectRole(1)">
                    管理者機能一覧<br>(どちらかを選択)
                </div>
            </div>
        </div>

        <div class="submit-container">
            <button type="submit" class="submit-btn">ログイン</button>
        </div>
    </form>
    <div class="submit-container">
        <a href="register.php" class="submit-btn" style="text-decoration: none; display: inline-block; background-color: #3498db; color: white;">新規登録はこちら</a>
    </div>
</div>

<script>
    function selectRole(role) {
        document.getElementById('selectedRole').value = role;

        // ボタンの見た目リセット
        const btnU = document.getElementById('btnUser');
        const btnA = document.getElementById('btnAdmin');
        btnU.classList.remove('active-user');
        btnA.classList.remove('active-admin');

        // 選択された方をハイライト
        if(role === 0) {
            btnU.classList.add('active-user');
        } else {
            btnA.classList.add('active-admin');
        }
    }
</script>

</body>
</html>
