// js/dashboard.js

// 选项卡切换逻辑
function switchTab(tabId) {
    // 隐藏所有内容
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });

    // 取消侧边栏高亮
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('active');
    });

    // 激活对应的选项卡
    if (document.getElementById('tab-' + tabId)) {
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // 高亮被点击的菜单按钮
    let targetNav = Array.from(document.querySelectorAll('.nav-item')).find(el => el.getAttribute('onclick') === `switchTab('${tabId}')`);
    if (targetNav) targetNav.classList.add('active');
}

// 固件安全下载接口处理 (拦截鉴权)
function downloadFirmware() {
    const statusDiv = document.getElementById('downloadStatus');
    statusDiv.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></svg> 正在请求服务器打包...';
    statusDiv.style.color = 'var(--info)';

    // 发起 AJAX 请求
    fetch('cgi-bin/download.cgi')
        .then(response => {
            if (!response.ok) {
                // 如果发现 HTTP 不是 200，说明报错了，我们强制解析服务器端返回的 JSON
                return response.json().then(errorData => {
                    throw new Error(errorData.message || "服务器拒绝了下载请求");
                });
            }
            // 鉴权通过，转为二进制 Blob
            return response.blob();
        })
        .then(blob => {
            // 下载逻辑：浏览器拦截并保存二进制文件
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = "firmware_backup.bin";
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);

            statusDiv.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 4px;"><path d="M20 6L9 17l-5-5"></path></svg> 下载准备就绪';
            statusDiv.style.color = 'var(--success)';
            setTimeout(() => statusDiv.innerHTML = '', 3000);
        })
        .catch(error => {
            console.error("Download Error:", error);
            statusDiv.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 4px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> 错误: ' + error.message;
            statusDiv.style.color = 'var(--danger)';

            if (error.message.includes("login") || error.message.includes("Access")) {
                setTimeout(() => window.location.href = 'index.html', 2000);
            }
        });
}

// 网络诊断 Ping 
function startPing() {
    const target = document.getElementById('pingTarget').value.trim();
    const out = document.getElementById('pingOutput');

    if (!target) {
        out.innerText = "请输入正确的 IP 或域名。";
        return;
    }

    out.innerText = `PING ${target} ...\n正在发送探测报文...\n`;

    fetch(`cgi-bin/ping.cgi?target=${encodeURIComponent(target)}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                out.innerText = data.output;
            } else {
                out.innerText += `\n[错误] ${data.message}\n`;
            }
        })
        .catch(err => {
            out.innerText += `\n[网络错误] 无法连接到诊断服务器: ${err.message}\n`;
        });
}

// 退出登录
function logout() {
    if (confirm("确定要退出 RouterOS 后台吗？")) {
        // 清除浏览器当次 Session Cookie
        document.cookie = "session_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        window.location.href = "index.html"; // 回到登录页
    }
}

// MAC 地址控制 (黑/白名单)
function validateMac(mac) {
    const regex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
    return regex.test(mac);
}

window.addBlacklist = function () {
    const idx = document.getElementById('settingsBlacklistIdx').value.trim();
    const mac = document.getElementById('settingsBlacklistMac').value.trim();
    const note = document.getElementById('settingsBlacklistNote').value.trim();

    if (!validateMac(mac)) { alert("请输入有效的 MAC 地址！格式例如 AA:BB:CC:DD:EE:FF"); return; }

    // In our new implementation, list.c uses action=add_black and parses idx, mac, note
    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_black&idx=${encodeURIComponent(idx)}&mac=${encodeURIComponent(mac)}&note=${encodeURIComponent(note)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('settingsBlacklistIdx').value = '';
                document.getElementById('settingsBlacklistMac').value = '';
                document.getElementById('settingsBlacklistNote').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

window.editBlacklist = function () {
    const idx = document.getElementById('settingsBlacklistIdx').value.trim();
    const mac = document.getElementById('settingsBlacklistMac').value.trim();
    const note = document.getElementById('settingsBlacklistNote').value.trim();

    if (!idx) { alert("修改必须输入需覆盖的标号 (Index)！"); return; }
    if (!validateMac(mac)) { alert("请输入有效的 MAC 地址！格式例如 AA:BB:CC:DD:EE:FF"); return; }

    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=edit_black&idx=${encodeURIComponent(idx)}&mac=${encodeURIComponent(mac)}&note=${encodeURIComponent(note)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('settingsBlacklistIdx').value = '';
                document.getElementById('settingsBlacklistMac').value = '';
                document.getElementById('settingsBlacklistNote').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

window.deleteBlacklist = function () {
    const idx = document.getElementById('deleteBlacklistIdx').value.trim();
    if (!idx) { alert("请输入欲删除的标号 (Index)！"); return; }
    if (!confirm(`警告：您确认要永久删除标号为 ${idx} 的黑名单规则吗？`)) return;

    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=del_black&idx=${encodeURIComponent(idx)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('deleteBlacklistIdx').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

window.addWhitelist = function () {
    const idx = document.getElementById('settingsWhitelistIdx').value.trim();
    const mac = document.getElementById('settingsWhitelistMac').value.trim();
    const note = document.getElementById('settingsWhitelistNote').value.trim();

    if (!validateMac(mac)) { alert("请输入有效的 MAC 地址！格式例如 11:22:33:44:55:66"); return; }

    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_white&idx=${encodeURIComponent(idx)}&mac=${encodeURIComponent(mac)}&note=${encodeURIComponent(note)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('settingsWhitelistIdx').value = '';
                document.getElementById('settingsWhitelistMac').value = '';
                document.getElementById('settingsWhitelistNote').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

window.editWhitelist = function () {
    const idx = document.getElementById('settingsWhitelistIdx').value.trim();
    const mac = document.getElementById('settingsWhitelistMac').value.trim();
    const note = document.getElementById('settingsWhitelistNote').value.trim();

    if (!idx) { alert("修改必须输入需覆盖的标号 (Index)！"); return; }
    if (!validateMac(mac)) { alert("请输入有效的 MAC 地址！格式例如 11:22:33:44:55:66"); return; }

    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=edit_white&idx=${encodeURIComponent(idx)}&mac=${encodeURIComponent(mac)}&note=${encodeURIComponent(note)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('settingsWhitelistIdx').value = '';
                document.getElementById('settingsWhitelistMac').value = '';
                document.getElementById('settingsWhitelistNote').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

window.deleteWhitelist = function () {
    const idx = document.getElementById('deleteWhitelistIdx').value.trim();
    if (!idx) { alert("请输入欲删除的标号 (Index)！"); return; }
    if (!confirm(`警告：您确认要永久删除标号为 ${idx} 的白名单规则吗？`)) return;

    fetch('cgi-bin/list.cgi', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=del_white&idx=${encodeURIComponent(idx)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                document.getElementById('deleteWhitelistIdx').value = '';
            } else { alert("操作失败：" + data.message); }
        }).catch(err => alert("网络错误 " + err.message));
}

// 固件在线升级 (假功能: 显示服务器维护)
window.fakeOnlineUpgrade = function (btn) {
    const originalText = btn.innerHTML;
    // 变成加载状态
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></svg> 正在连接更新服务器...';
    btn.disabled = true;

    // 模拟网络延迟后弹窗
    setTimeout(() => {
        alert("升级失败：抱歉，官方固件服务器正在例行维护中，请稍后再试。");
        // 恢复按钮状态
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 1500);
}

// 恢复出厂设置 (调用 CGI)
window.factoryReset = function () {
    if (!confirm("⚠️ 警告：恢复出厂设置将清除所有自定义配置（包括 WiFi、VPN、过滤列表等）。设备将立即重启。\n\n您确定要继续吗？")) {
        return;
    }

    const btn = document.querySelector('button[onclick="window.factoryReset()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></svg> 正在擦除 NVRAM...';
    btn.disabled = true;

    fetch('cgi-bin/restart.sh')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                // 模拟重启，跳转到登录页
                window.location.href = 'index.html';
            } else {
                alert("操作失败: " + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert("网络错误: " + err.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

// 密码显示/隐藏切换
window.togglePassword = function (icon) {
    const input = icon.previousElementSibling;
    if (input.type === "password") {
        input.type = "text";
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    } else {
        input.type = "password";
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    }
}

// 无线网络管理 (Wi-Fi Settings)
window.saveWifiConfig = function () {
    const ssid = document.getElementById('wifiSSID').value.trim();
    const pass = document.getElementById('wifiPass').value.trim();

    if (!ssid) {
        alert("SSID 不能为空！");
        return;
    }

    fetch('cgi-bin/wifi.cgi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=save&ssid=${encodeURIComponent(ssid)}&password=${encodeURIComponent(pass)}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Wi-Fi 配置已更新，路由器正在重启无线服务...");
            } else {
                alert("配置保存失败：" + data.message);
            }
        })
        .catch(err => alert("网络错误: " + err.message));
}

// VPN 高级设置功能
window.applyVpnConfig = function () {
    const btn = document.querySelector('#vpnConfigForm .btn-outline');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></svg> 保存中...';
    btn.disabled = true;

    // 收集前端表单数据封装成 JSON
    const payload = {
        action: "set",
        name: document.getElementById('vpnName').value,
        proto: document.getElementById('vpnProto').value,
        server: document.getElementById('vpnServer').value,
        user: document.getElementById('vpnUser').value,
        pass: document.getElementById('vpnPass').value,
        cert: document.getElementById('vpnCert').value,
        custom: document.getElementById('vpnCustom').value
    };

    // 发往后端接口 /cgi-bin/vpn.cgi
    fetch('cgi-bin/vpn.cgi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert("VPN 配置已就绪，缓冲数据已写入 NVRAM。");
            } else {
                alert("保存失败: " + data.message);
            }
        })
        .catch(err => {
            alert("网络错误: " + err.message);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

window.connectVpn = function () {
    const btn = document.getElementById('btnVpnConnect');
    const out = document.getElementById('vpnStatusOutput');
    const proto = document.getElementById('vpnProto').value;
    const name = document.getElementById('vpnName').value || 'target_vpn';

    btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></svg> 启动进程...';
    btn.disabled = true;
    out.style.display = 'block';
    out.innerText = `[INFO] Requesting kernel daemon for '${name}' via ${proto.toUpperCase()} interface...\n`;

    fetch('cgi-bin/vpn.cgi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: "apply", name: name })
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                out.innerText += `[INFO] ${data.message}\n`;
            } else {
                out.innerText += `[ERROR] ${data.message}\n`;
            }
        })
        .catch(err => {
            out.innerText += `[ERROR] Connection failed: ${err.message}\n`;
        })
        .finally(() => {
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> 建立连接';
            btn.disabled = false;
        });
}

// 初始化时强刷 Dashboard
window.onload = () => switchTab('dashboard');
