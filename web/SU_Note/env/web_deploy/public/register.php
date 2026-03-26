<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if (current_user() !== null) {
    redirect('/');
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'POST') {
    require_csrf_or_redirect('/register.php');

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        set_flash('error', '用户名和密码不能为空。');
        redirect('/register.php');
    }

    if (strlen($username) < 3 || strlen($username) > 32) {
        set_flash('error', '用户名长度需在 3-32 个字符之间。');
        redirect('/register.php');
    }

    if (strlen($password) < 6) {
        set_flash('error', '密码至少 6 位。');
        redirect('/register.php');
    }

    if (isset($_SESSION['users'][$username])) {
        set_flash('error', '该用户名已存在。');
        redirect('/register.php');
    }

    $_SESSION['users'][$username] = password_hash($password, PASSWORD_DEFAULT);
    set_flash('success', '注册成功，请登录。');
    redirect('/login.php');
}

$flash = pop_flash();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU-ezNote 注册</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f3f5f9;
            color: #1f2937;
        }
        .container {
            max-width: 460px;
            margin: 56px auto;
            background: #fff;
            border-radius: 12px;
            padding: 26px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        h1 {
            margin: 0 0 14px;
            font-size: 26px;
        }
        label {
            display: block;
            margin: 10px 0 6px;
            font-size: 14px;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            margin-top: 12px;
            width: 100%;
            padding: 10px;
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }
        .flash {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
        }
        .flash.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .flash.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .links {
            margin-top: 12px;
            font-size: 14px;
        }
        .links a {
            color: #1d4ed8;
            text-decoration: none;
        }
    </style>
</head>
<body>
<main class="container">
    <h1>注册</h1>

    <?php if (is_array($flash)): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="post" action="/register.php">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label for="username">用户名</label>
        <input id="username" name="username" type="text" minlength="3" maxlength="32" required>
        <label for="password">密码</label>
        <input id="password" name="password" type="password" minlength="6" required>
        <button type="submit">注册</button>
    </form>
    <div class="links">
        已有账号？<a href="/login.php">去登录</a>
    </div>
</main>
</body>
</html>
