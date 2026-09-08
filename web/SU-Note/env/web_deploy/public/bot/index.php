<?php
declare(strict_types=1);

require dirname(__DIR__) . '/auth.php';

$authUser = current_user();
if ($authUser === null) {
    redirect('/login.php');
}

$botBaseUrl = getenv('BOT_BASE_URL');
if (!is_string($botBaseUrl) || $botBaseUrl === '') {
    $botBaseUrl = 'http://127.0.0.1:80';
}

function bot_client_ip(): string
{
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    if (is_string($xff) && $xff !== '') {
        $parts = explode(',', $xff);
        $candidate = trim((string) ($parts[0] ?? ''));
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $candidate;
        }
    }

    $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? null;
    if (is_string($realIp) && $realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
        return $realIp;
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (is_string($remote) && $remote !== '') {
        return $remote;
    }

    return 'unknown';
}

function bot_rate_limit_remaining(string $ip, int $windowSeconds = 60, int $maxRequests = 3): int
{
    $windowSeconds = max(1, $windowSeconds);
    $maxRequests = max(1, $maxRequests);
    $dir = '/tmp/su-note-bot-rate-limit';

    if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
        return 0;
    }

    $path = $dir . '/' . sha1($ip) . '.ts';
    $now = time();
    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return 0;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            return 0;
        }

        rewind($fp);
        $raw = stream_get_contents($fp);
        $timestamps = [];
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $timestamp) {
                        if (is_int($timestamp) || (is_string($timestamp) && ctype_digit($timestamp))) {
                            $timestamps[] = (int) $timestamp;
                        }
                    }
                } elseif (ctype_digit($raw)) {
                    $timestamps[] = (int) $raw;
                }
            }
        }

        $timestamps = array_values(array_filter(
            $timestamps,
            static fn (int $timestamp): bool => $timestamp > 0 && ($now - $timestamp) < $windowSeconds
        ));

        if (count($timestamps) >= $maxRequests) {
            sort($timestamps, SORT_NUMERIC);
            $oldest = $timestamps[0] ?? 0;
            if ($oldest > 0) {
                return max(1, $windowSeconds - ($now - $oldest));
            }
        }

        $timestamps[] = $now;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($timestamps, JSON_UNESCAPED_SLASHES));
        fflush($fp);

        return 0;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function bot_max_concurrency(): int
{
    $raw = getenv('BOT_MAX_CONCURRENCY');
    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw !== '' && ctype_digit($raw)) {
            $value = (int) $raw;
            if ($value >= 1) {
                return min($value, 32);
            }
        }
    }

    throw new RuntimeException('BOT_MAX_CONCURRENCY must be loaded from .config');
}

function bot_concurrency_acquire(int $maxConcurrency, int $staleAfterSeconds = 900): ?string
{
    $maxConcurrency = max(1, $maxConcurrency);
    $staleAfterSeconds = max(60, $staleAfterSeconds);
    $dir = '/tmp/su-note-bot-slots';
    if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
        return null;
    }

    $lockPath = $dir . '/.lock';
    $fp = @fopen($lockPath, 'c+');
    if ($fp === false) {
        return null;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            return null;
        }

        $now = time();
        $active = 0;
        $files = glob($dir . '/slot-*.json');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (!is_string($file) || !is_file($file)) {
                    continue;
                }

                $startedAt = 0;
                $raw = @file_get_contents($file);
                if (is_string($raw) && $raw !== '') {
                    $data = json_decode($raw, true);
                    if (is_array($data) && isset($data['started_at']) && is_int($data['started_at'])) {
                        $startedAt = $data['started_at'];
                    }
                }

                if ($startedAt <= 0) {
                    $mtime = @filemtime($file);
                    if (is_int($mtime) && $mtime > 0) {
                        $startedAt = $mtime;
                    } else {
                        $startedAt = $now;
                    }
                }

                if (($now - $startedAt) > $staleAfterSeconds) {
                    @unlink($file);
                    continue;
                }

                $active += 1;
            }
        }

        if ($active >= $maxConcurrency) {
            return null;
        }

        try {
            $token = bin2hex(random_bytes(16));
        } catch (Throwable) {
            $token = sha1((string) microtime(true) . (string) mt_rand());
        }

        $slotPath = $dir . '/slot-' . $token . '.json';
        $payload = json_encode([
            'pid' => function_exists('getmypid') ? getmypid() : 0,
            'started_at' => $now,
            'ip' => bot_client_ip(),
        ], JSON_UNESCAPED_SLASHES);
        if (!is_string($payload) || @file_put_contents($slotPath, $payload, LOCK_EX) === false) {
            return null;
        }

        return $slotPath;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function bot_concurrency_release(?string $slotPath): void
{
    if (!is_string($slotPath) || $slotPath === '') {
        return;
    }

    $dir = '/tmp/su-note-bot-slots';
    $lockPath = $dir . '/.lock';
    $fp = @fopen($lockPath, 'c+');
    if ($fp === false) {
        @unlink($slotPath);
        return;
    }

    try {
        if (flock($fp, LOCK_EX)) {
            @unlink($slotPath);
        }
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($requestMethod === 'POST') {
    require_csrf_or_redirect('/bot/');

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'logout') {
        unset($_SESSION['auth_user']);
        set_flash('success', '已退出登录。');
        redirect('/login.php');
    }

    if ($action === 'visit') {
        $targetInput = trim((string) ($_POST['url'] ?? ''));
        if ($targetInput === '') {
            set_flash('error', '目标 URL 不能为空。');
            redirect('/bot/');
        }

        if (str_starts_with($targetInput, '/')) {
            $targetInput = rtrim($botBaseUrl, '/') . $targetInput;
        }

        $target = parse_url($targetInput);
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));
        $targetHost = strtolower((string) ($target['host'] ?? ''));

        if ($targetScheme === '' || $targetHost === '') {
            set_flash('error', '目标 URL 无效。');
            redirect('/bot/');
        }

        if ($targetScheme !== 'http' && $targetScheme !== 'https') {
            set_flash('error', '仅允许 http/https URL。');
            redirect('/bot/');
        }

        $clientIp = bot_client_ip();
        $remaining = bot_rate_limit_remaining($clientIp, 60, 3);
        if ($remaining > 0) {
            set_flash('error', '请求过于频繁：同一 IP 每 60 秒仅允许提交 3 次（剩余 ' . $remaining . ' 秒）。');
            redirect('/bot/');
        }

        $maxConcurrency = bot_max_concurrency();
        $slotPath = bot_concurrency_acquire($maxConcurrency, 900);
        if ($slotPath === null) {
            set_flash('error', '当前 Bot 任务过多：最大并发 ' . $maxConcurrency . '，请稍后再试。');
            redirect('/bot/');
        }

        try {
            $adminUser = system_admin_username();
            $botSessionId = create_impersonated_session($adminUser);
            $chromeBin = getenv('CHROME_BIN');
            if (!is_string($chromeBin) || $chromeBin === '') {
                $chromeBin = '/usr/bin/google-chrome';
            }

            $envPrefix = 'BOT_BASE_URL=' . escapeshellarg($botBaseUrl) . ' CHROME_BIN=' . escapeshellarg($chromeBin) . ' ';
            $cmd = $envPrefix
                . 'timeout 60s node /opt/su-eznote-bot/bot.js '
                . escapeshellarg($targetInput) . ' '
                . escapeshellarg($botSessionId)
                . ' 2>&1';

            $output = [];
            $code = 1;
            exec($cmd, $output, $code);
            $_SESSION['bot_last_output'] = implode("\n", array_slice($output, -30));

            if ($code === 0) {
                set_flash('success', 'Bot 已成功访问目标。');
            } elseif ($code === 124) {
                set_flash('error', 'Bot 超时（最多 1 分钟）。');
            } else {
                set_flash('error', 'Bot 执行失败，退出码：' . $code);
            }
        } finally {
            bot_concurrency_release($slotPath);
        }

        redirect('/bot/?url=' . urlencode($targetInput));
    }
}

$flash = pop_flash();

$lastOutput = $_SESSION['bot_last_output'] ?? '';
if (!is_string($lastOutput)) {
    $lastOutput = '';
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTF XSS Bot</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f3f5f9;
            color: #1f2937;
        }
        .container {
            max-width: 860px;
            margin: 34px auto;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0;
            font-size: 26px;
        }
        .card {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
        }
        .flash {
            margin: 0 0 12px;
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
        label {
            display: block;
            margin: 8px 0 6px;
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
            min-height: 180px;
            resize: vertical;
            background: #0f172a;
            color: #e2e8f0;
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
        .hint {
            margin: 8px 0 0;
            font-size: 13px;
            color: #4b5563;
        }
    </style>
</head>
<body>
<main class="container">
    <div class="topbar">
        <h1>CTF XSS Bot</h1>
        <div class="actions">
            <a class="secondary" href="/">返回首页</a>
            <form method="post" action="/bot/" style="margin: 0;">
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
        <form method="post" action="/bot/">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="visit">
            <label for="url">目标 URL（仅允许 http/https）</label>
            <input id="url" name="url" type="text" required>
            <button type="submit">让 Bot 访问</button>
        </form>
    </section>

    <section class="card">
        <label for="output">最近一次 Bot 输出</label>
        <textarea id="output" readonly><?= e($lastOutput === '' ? 'No output yet.' : $lastOutput) ?></textarea>
    </section>
</main>
</body>
</html>
