#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <ctype.h>
#include "../lib/libutils.h"
#include "../www/auth.h"

// 简单的 JSON 字段提取函数
// 假设格式为 "key":"value" 或 "key": "value"
void extract_json_string(const char *json, const char *key, char *out, size_t max_len) {
    out[0] = '\0';
    char search_key[128];
    snprintf(search_key, sizeof(search_key), "\"%s\"", key);
    
    char *p = strstr(json, search_key);
    if (!p) return;
    
    p += strlen(search_key);
    while (*p == ' ' || *p == ':') p++; // 跳过冒号和空格
    
    if (*p == '"') {
        p++; // 跳过开头的双引号
        size_t i = 0;
        while (*p != '"' && *p != '\0' && i < max_len) {
            // 处理简单的转义
            if (*p == '\\' && *(p+1) != '\0') {
                // 1. 优先尝试匹配 \\xHH 或 \xHH 十六进制转义
                if (*(p+1) == '\\' && *(p+2) == 'x' && isxdigit(*(p+3)) && isxdigit(*(p+4))) {
                    // 匹配 \\xHH (JSON 会把反斜杠转义成 \\)
                    char hex[3] = { *(p+3), *(p+4), 0 };
                    out[i++] = (char)strtol(hex, NULL, 16);
                    p += 4;
                } 
                else if (*(p+1) == 'x' && isxdigit(*(p+2)) && isxdigit(*(p+3))) {
                    // 匹配 \xHH (原生输入)
                    char hex[3] = { *(p+2), *(p+3), 0 };
                    out[i++] = (char)strtol(hex, NULL, 16);
                    p += 3;
                }
                else {
                    // 2. 标准 JSON 转义
                    p++;
                    if (*p == 'n') out[i++] = '\n';
                    else if (*p == 'r') out[i++] = '\r';
                    else if (*p == '"') out[i++] = '"';
                    else if (*p == '\\') out[i++] = '\\';
                    else out[i++] = *p;
                }
            } else {
                out[i++] = *p;
            }
            p++;
        }
        // 如果还有剩余空间，强制补齐 NULL (虽然某些漏洞利用点可能需要不补齐，
        // 但为了程序稳定，除非明确要求，否则加上)
        if (i < max_len) {
            out[i] = '\0';
        }
    }
}

// 简单的 Base64 解码函数 (用于 PWN Payload 传输)
// 解码后的数据会覆盖写入 original_str，并返回实际长度
size_t decode_base64_in_place(char *str) {
    static const int b64_index[256] = {
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0, 62, 63, 62, 62, 63,
        52, 53, 54, 55, 56, 57, 58, 59, 60, 61,  0,  0,  0,  0,  0,  0,
        0,  0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14,
        15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25,  0,  0,  0,  0,  0,
        0, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
        41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,
        0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0,  0
    };

    size_t in_len = strlen(str);
    if (in_len == 0) return 0;
    
    // 如果字符串以 B64: 开头，则进行解码
    if (strncmp(str, "B64:", 4) != 0) {
        return in_len; 
    }
    
    char *in = str + 4;
    in_len -= 4;
    size_t out_len = 0;
    
    for (size_t i = 0; i < in_len; i += 4) {
        int n = b64_index[(unsigned char)in[i]] << 18 |
                b64_index[(unsigned char)in[i+1]] << 12 |
                b64_index[(unsigned char)in[i+2]] << 6 |
                b64_index[(unsigned char)in[i+3]];
                
        str[out_len++] = n >> 16;
        if (in[i+2] != '=') str[out_len++] = n >> 8 & 0xFF;
        if (in[i+3] != '=') str[out_len++] = n & 0xFF;
    }
    return out_len;
}

int main() {
    printf("HTTP/1.1 200 OK\r\n");
    printf("Content-Type: application/json\r\n\r\n");

    // 鉴权拦截 (可选，看题目总体控制台逻辑)
    if (!check_auth()) {
        printf("{\"status\": \"error\", \"message\": \"Unauthorized Access! Please login first.\"}\n");
        return 0;
    }

    char *method = getenv("REQUEST_METHOD");
    if (!method || strcmp(method, "POST") != 0) {
        printf("{\"status\": \"error\", \"message\": \"Only POST allowed\"}\n");
        return 0;
    }

    char *len_str = getenv("CONTENT_LENGTH");
    if (!len_str) {
        printf("{\"status\": \"error\", \"message\": \"Content-Length required\"}\n");
        return 0;
    }

    int len = atoi(len_str);
    if (len <= 0 || len > 16384) {
        printf("{\"status\": \"error\", \"message\": \"Invalid Content-Length\"}\n");
        return 0;
    }

    char *json_data = malloc(len + 1);
    if (!json_data) {
        printf("{\"status\": \"error\", \"message\": \"Memory allocation failed\"}\n");
        return 0;
    }

    fread(json_data, 1, len, stdin);
    json_data[len] = '\0';

    // 定义用于承装 VPN 配置结果的结构体
    struct __attribute__((packed)) vpn_recv {
        char action[0x20];
        char name[0x20];
        char proto[0x20];
        char server[0x30];
        char user[0x20];
        char pass[0x20];
        char cert[8];
        char gap[1];
        char custom[3000];
    } req;

    memset(&req, 0, sizeof(req));

    // 解析各个字段
    extract_json_string(json_data, "action", req.action, sizeof(req.action));
    extract_json_string(json_data, "name", req.name, sizeof(req.name)); 
    extract_json_string(json_data, "proto", req.proto, sizeof(req.proto));
    extract_json_string(json_data, "server", req.server, sizeof(req.server)); 
    extract_json_string(json_data, "user", req.user, sizeof(req.user));
    extract_json_string(json_data, "pass", req.pass, sizeof(req.pass));
    extract_json_string(json_data, "cert", req.cert, sizeof(req.cert));
    extract_json_string(json_data, "custom", req.custom, sizeof(req.custom));

    free(json_data);
    
    // 尝试对 custom 字段进行 base64 解码，支持二进制 Payload 传输
    size_t custom_real_len = decode_base64_in_place(req.custom);

    if (strcmp(req.action, "set") == 0) {
        if (CFG_SET(0x9313f7e0, &req, sizeof(req)) == 0) {
            printf("{\"status\": \"success\", \"message\": \"VPN configuration saved.\", \"data\": \"%s\"}\n", req.name);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC error saving VPN config.\"}\n");
        }
    } else if (strcmp(req.action, "edit") == 0) {
        // Edit Custom Payload
        if (CFG_SET(0xe6133f10, req.custom, custom_real_len) == 0) {
            printf("{\"status\": \"success\", \"message\": \"VPN custom options updated.\", \"data\": \"%s\"}\n", req.name);
        } else {
            printf("{\"status\": \"error\", \"message\": \"IPC error updating VPN custom options.\"}\n");
        }
    } else if (strcmp(req.action, "apply") == 0) {
        // Apply VPN (Trigger mainproc callback, always return success to frontend)
        int dummy = 1;
        CFG_SET(0x96e7ff60, &dummy, sizeof(dummy)); // Send mtype: 0x96e7ff60 
        printf("{\"status\": \"success\", \"message\": \"VPN connected successfully.\", \"data\": \"%s\"}\n", req.name);

    } else {
        printf("{\"status\": \"error\", \"message\": \"Unknown or missing action\"}\n");
    }

    return 0;
}
