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
if ($requestMethod === 'POST' && (string) ($_POST['action'] ?? '') === 'logout') {
    unset($_SESSION['auth_user']);
    set_flash('success', '已退出登录。');
    redirect('/login.php');
}

$notes = $_SESSION['notes'][$authUser];
$searchQuery = trim((string) ($_GET['q'] ?? ''));

$filteredNotes = [];
if ($searchQuery !== '') {
    $filteredNotes = array_values(array_filter($notes, static function ($note) use ($searchQuery): bool {
        if (!is_array($note)) {
            return false;
        }
        $title = (string) ($note['title'] ?? '');
        $content = (string) ($note['content'] ?? '');

        return stripos($title, $searchQuery) !== false || stripos($content, $searchQuery) !== false;
    }));
}

$selectedId = trim((string) ($_GET['note'] ?? ''));
if ($selectedId === '' && isset($filteredNotes[0]['id']) && is_string($filteredNotes[0]['id'])) {
    $selectedId = $filteredNotes[0]['id'];
}

$selectedNote = null;
foreach ($filteredNotes as $note) {
    if (!is_array($note)) {
        continue;
    }
    if (($note['id'] ?? '') === $selectedId) {
        $selectedNote = $note;
        break;
    }
}

// Keep no-store for HTTP cache, while allowing Chrome's modern BFCache policy to decide.
header('Cache-Control: no-store');
header('Vary: Cookie');
header('Content-Type: text/html; charset=UTF-8');

$flash = pop_flash();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>笔记搜索结果</title>
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
        .search {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .search input {
            flex: 1;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }
        .layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 16px;
            min-height: 520px;
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
            max-height: 470px;
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
            background: #4b5563;
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
        <h1>笔记搜索</h1>
        <div class="actions">
            <a class="secondary" href="/">返回列表</a>
            <form method="post" action="/search.php" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit">退出登录</button>
            </form>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form class="search" method="get" action="/search.php">
        <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="输入关键词，按标题和内容搜索">
        <button type="submit">搜索</button>
    </form>
    <?php if ($searchQuery !== '' && count($filteredNotes) === 0): ?>
        <p class="empty" style="margin: 0 0 12px;">未命中关键词会在离开页面时被记录，用于下次搜索推荐。</p>
    <?php endif; ?>

    <div class="layout">
        <aside class="sidebar">
            <h2>结果 (<?= count($filteredNotes) ?>)</h2>
            <div class="note-list">
                <?php if ($searchQuery === ''): ?>
                    <div class="empty">请输入关键词开始搜索。</div>
                <?php elseif (count($filteredNotes) === 0): ?>
                    <div class="empty">没有匹配“<?= e($searchQuery) ?>”的笔记。</div>
                <?php else: ?>
                    <?php foreach ($filteredNotes as $note): ?>
                        <?php
                        $noteId = (string) ($note['id'] ?? '');
                        $isActive = $noteId !== '' && $noteId === $selectedId;
                        $noteUrl = '/search.php?q=' . urlencode($searchQuery) . '&note=' . urlencode($noteId);
                        ?>
                        <a class="note-item <?= $isActive ? 'active' : '' ?>" href="<?= e($noteUrl) ?>">
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
                    <h2>未选中结果</h2>
                    <p class="text">请从左侧结果列表中选择一条笔记查看。</p>
                </article>
            <?php endif; ?>
        </section>
    </div>
</main>
<script>
(() => {
    const searchQuery = <?= json_encode($searchQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const isZeroResult = <?= ($searchQuery !== '' && count($filteredNotes) === 0) ? 'true' : 'false' ?>;
    const isSearchDone = searchQuery !== '';

    if (isSearchDone) {
        window.setTimeout(() => {
            try {
                window.history.back();
            } catch (e) {}
        }, 5000);
    }

    if (!isZeroResult) return;

    const onUnload = () => {
        try {
            localStorage.setItem('su_eznote_last_miss_query', searchQuery);
            localStorage.setItem('su_eznote_last_miss_at', String(Date.now()));
        } catch (e) {}
    };

    window.addEventListener('unload', onUnload);
})();
</script>
</body>
</html>
