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
    require_csrf_or_redirect('/');

    if ((string) ($_POST['action'] ?? '') === 'logout') {
        unset($_SESSION['auth_user']);
        set_flash('success', '已退出登录。');
        redirect('/login.php');
    }
}

$notes = $_SESSION['notes'][$authUser];
$selectedId = trim((string) ($_GET['note'] ?? ''));
if ($selectedId === '' && isset($notes[0]['id']) && is_string($notes[0]['id'])) {
    $selectedId = $notes[0]['id'];
}

$selectedNote = null;
foreach ($notes as $note) {
    if (!is_array($note)) {
        continue;
    }
    if (($note['id'] ?? '') === $selectedId) {
        $selectedNote = $note;
        break;
    }
}

$flash = pop_flash();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>笔记列表</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f3f5f9;
            color: #1f2937;
        }
        .container {
            max-width: 980px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
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
        .layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 16px;
            min-height: 560px;
        }
        .sidebar, .content {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
        }
        .sidebar {
            padding: 12px;
        }
        .sidebar h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .note-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 520px;
            overflow: auto;
        }
        .note-item {
            display: block;
            text-decoration: none;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            background: #fff;
            color: #111827;
        }
        .note-item.active {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .note-title {
            margin: 0 0 4px;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .note-time {
            font-size: 12px;
            color: #6b7280;
        }
        .empty {
            font-size: 14px;
            color: #6b7280;
            padding: 8px 2px;
        }
        .content {
            padding: 14px;
        }
        .detail {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 14px;
        }
        .detail h2 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .detail .meta {
            margin: 0 0 10px;
            font-size: 12px;
            color: #6b7280;
        }
        .detail .text {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.55;
            font-size: 14px;
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
            background: #1d4ed8;
        }
        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main class="container">
    <div class="topbar">
        <h1>笔记列表</h1>
        <div class="actions">
            <a href="/create_note.php">创建笔记</a>
            <a class="secondary" href="/bot/">CTF XSS Bot</a>
            <a class="secondary" href="/search.php">搜索笔记</a>
            <form method="post" action="/" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit">退出登录</button>
            </form>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="layout">
        <aside class="sidebar">
            <h2>笔记列表 (<?= count($notes) ?>)</h2>
            <div class="note-list">
                <?php if (count($notes) === 0): ?>
                    <div class="empty">还没有笔记，点击右上角“创建笔记”。</div>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <?php
                        $noteId = (string) ($note['id'] ?? '');
                        $isActive = $noteId !== '' && $noteId === $selectedId;
                        ?>
                        <a class="note-item <?= $isActive ? 'active' : '' ?>" href="/?note=<?= e($noteId) ?>">
                            <p class="note-title"><?= e($note['title'] ?? '未命名') ?></p>
                            <div class="note-time"><?= e($note['created_at'] ?? '') ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
        <section class="content">
            <?php if (is_array($selectedNote)): ?>
                <article class="detail">
                    <h2><?= e($selectedNote['title'] ?? '') ?></h2>
                    <p class="meta">创建时间：<?= e($selectedNote['created_at'] ?? '') ?> / 用户：<?= e($authUser) ?></p>
                    <p class="text"><?= e($selectedNote['content'] ?? '') ?></p>
                </article>
            <?php else: ?>
                <article class="detail">
                    <h2>未选中笔记</h2>
                    <p class="text">请从左侧列表中选择笔记查看内容。</p>
                </article>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
