<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

$authUser = current_user();
if ($authUser === null) {
    redirect('/login.php');
}

if (!isset($_SESSION['notes']) || !is_array($_SESSION['notes'])) {
    $_SESSION['notes'] = [];
}

if (!isset($_SESSION['notes'][$authUser]) || !is_array($_SESSION['notes'][$authUser])) {
    $_SESSION['notes'][$authUser] = [];
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($requestMethod === 'POST') {
    require_csrf_or_redirect('/create_note.php');

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'logout') {
        unset($_SESSION['auth_user']);
        set_flash('success', '已退出登录。');
        redirect('/login.php');
    }

    if ($action === 'create_note') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));

        if ($title === '' || $content === '') {
            set_flash('error', '标题和内容不能为空。');
            redirect('/create_note.php');
        }

        if (strlen($title) > 120) {
            set_flash('error', '标题不能超过 120 个字符。');
            redirect('/create_note.php');
        }

        if (strlen($content) > 10000) {
            set_flash('error', '内容不能超过 10000 个字符。');
            redirect('/create_note.php');
        }

        $noteId = bin2hex(random_bytes(8));
        $note = [
            'id' => $noteId,
            'title' => $title,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        array_unshift($_SESSION['notes'][$authUser], $note);
        set_flash('success', '笔记已创建。');
        redirect('/?note=' . urlencode($noteId));
    }
}

$flash = pop_flash();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU-ezNote 创建笔记</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f3f5f9;
            color: #1f2937;
        }
        .container {
            max-width: 820px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0;
            font-size: 26px;
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
        .card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }
        label {
            display: block;
            margin: 10px 0 6px;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            font-family: inherit;
        }
        textarea {
            min-height: 220px;
            resize: vertical;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        a, button {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }
        .secondary {
            background: #4b5563;
        }
    </style>
</head>
<body>
<main class="container">
    <div class="topbar">
        <h1>创建笔记</h1>
        <div class="actions">
            <a class="secondary" href="/">返回列表</a>
            <form method="post" action="/create_note.php" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit">退出登录</button>
            </form>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="card">
        <form method="post" action="/create_note.php">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_note">
            <label for="title">标题</label>
            <input id="title" name="title" type="text" maxlength="120" required>
            <label for="content">内容</label>
            <textarea id="content" name="content" maxlength="10000" required></textarea>
            <button type="submit">保存笔记</button>
        </form>
    </section>
</main>
</body>
</html>
