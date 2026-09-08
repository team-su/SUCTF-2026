#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <ctype.h>
#include "../www/auth.h"
#include "../lib/libutils.h"

struct __attribute__((packed)) list_req {
    int idx;            // 0x00 - 0x04: 数组索引 (用于精准控制释放哪个堆块)
    char mac[0x10];     // 16 bytes
    char note[0x1c];    // 28 bytes
};

// ---------------------------------------------------------
// 工具函数：解析 URL 参数 (支持 POST Body 和 GET Query)
// ---------------------------------------------------------
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
    // 强制输出 Content-Type 为 JSON，符合现代路由器的 API 规范
    printf("Content-Type: application/json\r\n\r\n");

    // [ 鉴权拦截 ]
    if (!check_auth()) {
        printf("{\"status\": \"error\", \"message\": \"Unauthorized Access! Please login first.\"}\n");
        return 0;
    }

    // [ 接收数据载荷 ]
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
    } else {
        char *qs = getenv("QUERY_STRING");
        if (qs) {
            strncpy(input, qs, sizeof(input) - 1);
        }
    }

    // [ 提取路由动作 ]
    char action[64] = {0};
    get_param(input, "action", action, sizeof(action));

    // =========================================================
    // [ 路由分发器 - 漏洞利用原语映射 ]
    // =========================================================

    // 1. 新增/编辑黑名单 (Heap Allocation: Malloc 0x30 -> 走向 Tcache/Fastbin)
    if (strcmp(action, "add_black") == 0 || strcmp(action, "edit_black") == 0) {
        struct list_req req;
        memset(&req, 0, sizeof(req));
        
        char idx_str[16] = {0};
        get_param(input, "idx", idx_str, sizeof(idx_str));
        req.idx = atoi(idx_str);
        
        if (req.idx < 0 || req.idx >= 10) {
            printf("{\"status\": \"error\", \"message\": \"Invalid index (0-9 allowed)!\"}\n");
            return 0;
        }

        get_param(input, "mac", req.mac, sizeof(req.mac));
        get_param(input, "note", req.note, sizeof(req.note));
        
        // 发送 IPC 给后台
        if (CFG_SET(0x74122f00, &req, sizeof(req)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"Blacklist %d updated successfully.\"}\n", req.idx);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC Error: Failed to update blacklist!\"}\n");
        }
    } 
    
    // 2. 删除黑名单 (Heap Free: Free 0x30 -> Tcache/Fastbin)
    else if (strcmp(action, "del_black") == 0) {
        char idx_str[16] = {0};
        get_param(input, "idx", idx_str, sizeof(idx_str));
        int idx = atoi(idx_str);
        
        if (idx < 0 || idx >= 10) {
            printf("{\"status\": \"error\", \"message\": \"Invalid index (0-9 allowed)!\"}\n");
            return 0;
        }

        // 发送 IPC 给后台触发 Free
        if (CFG_SET(0x32ee2000, &idx, sizeof(int)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"Blacklist %d deleted.\"}\n", idx);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC Error: Failed to delete blacklist!\"}\n");
        }
    }

    // 3. 新增/编辑白名单 (同样的堆分配原语，增加选手的容错率)
    else if (strcmp(action, "add_white") == 0 || strcmp(action, "edit_white") == 0) {
        struct list_req req;
        memset(&req, 0, sizeof(req));
        
        char idx_str[16] = {0};
        get_param(input, "idx", idx_str, sizeof(idx_str));
        req.idx = atoi(idx_str);
        
        if (req.idx < 0 || req.idx >= 10) {
            printf("{\"status\": \"error\", \"message\": \"Invalid index (0-9 allowed)!\"}\n");
            return 0;
        }

        get_param(input, "mac", req.mac, sizeof(req.mac));
        get_param(input, "note", req.note, sizeof(req.note));
        
        if (CFG_SET(0x74122c02, &req, sizeof(req)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"Whitelist %d updated successfully.\"}\n", req.idx);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC Error: Failed to update whitelist!\"}\n");
        }
    }

    // 4. 删除白名单 (Heap Free)
    else if (strcmp(action, "del_white") == 0) {
        char idx_str[16] = {0};
        get_param(input, "idx", idx_str, sizeof(idx_str));
        int idx = atoi(idx_str);
        
        if (idx < 0 || idx >= 10) {
            printf("{\"status\": \"error\", \"message\": \"Invalid index (0-9 allowed)!\"}\n");
            return 0;
        }

        if (CFG_SET(0x32ef2030, &idx, sizeof(int)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"Whitelist %d deleted.\"}\n", idx);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC Error: Failed to delete whitelist!\"}\n");
        }
    }

    // 未知动作处理
    else {
        printf("{\"status\": \"error\", \"message\": \"Unknown or missing 'action' parameter.\"}\n");
    }

    return 0;
}