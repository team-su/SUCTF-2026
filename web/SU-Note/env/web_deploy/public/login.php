<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'POST') {
    require_csrf_or_redirect('/login.php');

    $action = (string) ($_POST['action'] ?? 'login');

    if ($action === 'logout') {
        unset($_SESSION['auth_user']);
        set_flash('success', '已退出登录。');
        redirect('/login.php');
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $storedHash = $_SESSION['users'][$username] ?? null;

    if ($username === '' || $password === '') {
        set_flash('error', '用户名和密码不能为空。');
        redirect('/login.php');
    }

    if (!is_string($storedHash) || !password_verify($password, $storedHash)) {
        set_flash('error', '用户名或密码错误。');
        redirect('/login.php');
    }

    $_SESSION['auth_user'] = $username;
    set_flash('success', '登录成功。');
    redirect('/');
}

$authUser = current_user();
$flash = pop_flash();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU-ezNote 登录</title>
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
        .welcome {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 14px;
            margin: 0 0 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<main class="container">
    <h1>登录</h1>

    <?php if (is_array($flash)): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($authUser !== null): ?>
        <div class="welcome">
            当前已登录：<strong><?= e($authUser) ?></strong>
        </div>
        <div class="links">
            <a href="/">进入首页</a>
        </div>
        <form method="post" action="/login.php">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit">退出登录</button>
        </form>
    <?php else: ?>
        <form method="post" action="/login.php">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="login">
            <label for="username">用户名</label>
            <input id="username" name="username" type="text" required>
            <label for="password">密码</label>
            <input id="password" name="password" type="password" required>
            <button type="submit">登录</button>
        </form>
        <div class="links">
            还没有账号？<a href="/register.php">去注册</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
