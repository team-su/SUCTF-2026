#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/stat.h>

// 检查用户是否已登录。
// 返回 1 表示已登录，返回 0 表示未登录。
int check_auth() {
    // 1. 获取客户端传来的 Cookie
    char *cookie = getenv("HTTP_COOKIE");
    if (cookie == NULL) {
        return 0;
    }

    // 2. 从 Cookie 字符串中提取 session_id=...
    // 注意：实际环境 Cookie 可能包含多个项，这里简化为查找 session_id=
    char *session_start = strstr(cookie, "session_id=");
    if (session_start == NULL) {
        return 0;
    }
    
    session_start += 11; // 跳过 "session_id="
    
    // 提取 session_id 的值，直到遇到分号或字符串结束
    char session_id[128] = {0};
    int i = 0;
    while (session_start[i] != ';' && session_start[i] != '\0' && i < sizeof(session_id) - 1) {
        session_id[i] = session_start[i];
        i++;
    }
    session_id[i] = '\0';

    // 防止目录穿越攻击恶意读取系统文件
    // 虽然我们在 WebServer 中做了基础防范，但在具体业务逻辑中对输入做过滤也是必要的深度防御。
    if (strchr(session_id, '/') != NULL || strstr(session_id, "..") != NULL) {
        return 0;
    }

    // 3. 检查 tmp/sessions 目录下是否存在对应的 Session 文件
    char session_file[512];
    snprintf(session_file, sizeof(session_file), "./tmp/sessions/%s", session_id);
    
    struct stat st;
    if (stat(session_file, &st) == 0 && S_ISREG(st.st_mode)) {
        // 文件存在且为普通文件，认为登录有效
        return 1;
    }

    // Session 不存在或已过期
    return 0;
}
