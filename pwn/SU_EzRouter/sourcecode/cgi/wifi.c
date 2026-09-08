#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <ctype.h>
#include "../www/auth.h"
#include "../lib/libutils.h"

// 提取查询参数 (从 list.c 引入的通用逻辑)
static void get_param(const char *query, const char *key, char *out, size_t out_len) {
    out[0] = '\0';
    if (!query || !key) return;

    const char *p = query;
    size_t key_len = strlen(key);

    while (p && *p) {
        if (strncmp(p, key, key_len) == 0 && p[key_len] == '=') {
            p += key_len + 1;
            const char *end = strchr(p, '&');
            size_t len = end ? (size_t)(end - p) : strlen(p);
            if (len >= out_len) len = out_len - 1;
            strncpy(out, p, len);
            out[len] = '\0';
            url_decode(out);
            return;
        }
        p = strchr(p, '&');
        if (p) p++;
    }
}

int main() {
    printf("HTTP/1.1 200 OK\r\n");
    // 强制输出 Content-Type 为 JSON
    printf("Content-Type: application/json\r\n\r\n");

    // 鉴权
    if (!check_auth()) {
        printf("{\"status\": \"error\", \"message\": \"Unauthorized Access!\"}\n");
        return 0;
    }

    char *method = getenv("REQUEST_METHOD");
    char input[4096] = {0};

    if (method && strcmp(method, "POST") == 0) {
        char *len_str = getenv("CONTENT_LENGTH");
        if (len_str) {
            int len = atoi(len_str);
            if (len > 0 && len < sizeof(input)) {
                fread(input, 1, len, stdin);
            }
        }
    }

    char action[64] = {0};
    get_param(input, "action", action, sizeof(action));

    if (strcmp(action, "save") == 0) {
        // 定义 Wi-Fi 配置结构体
        struct {
            char ssid[0x40];
            char password[0x40]; // Allow 128 to exploit mainproc.c vulnerabilities
        } req;
        memset(&req, 0, sizeof(req));

        get_param(input, "ssid", req.ssid, sizeof(req.ssid));
        get_param(input, "password", req.password, sizeof(req.password));

        // 发送给 mainproc
        if (CFG_SET(0x6374fe30, &req, sizeof(req)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"Wi-Fi settings saved and reported to kernel.\", \"ssid\": \"%s\"}\n", req.ssid);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC Error: Failed to notify management process.\"}\n");
        }
    } else {
        printf("{\"status\": \"error\", \"message\": \"Invalid action for wifi.cgi\"}\n");
    }

    return 0;
}
