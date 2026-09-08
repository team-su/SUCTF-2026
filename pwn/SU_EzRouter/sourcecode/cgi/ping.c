#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "../lib/libutils.h"

// 简单的 urldecode 函数
void urldecode(char *dst, const char *src) {
    char a, b;
    while (*src) {
        if ((*src == '%') && ((a = src[1]) && (b = src[2]))) {
            if (a >= 'a' && a <= 'f') a -= 'a' - 10;
            else if (a >= 'A' && a <= 'F') a -= 'A' - 10;
            else a -= '0';
            
            if (b >= 'a' && b <= 'f') b -= 'a' - 10;
            else if (b >= 'A' && b <= 'F') b -= 'A' - 10;
            else b -= '0';
            
            *dst++ = 16 * a + b;
            src += 3;
        } else if (*src == '+') {
            *dst++ = ' ';
            src++;
        } else {
            *dst++ = *src++;
        }
    }
    *dst++ = '\0';
}

int main() {
    printf("HTTP/1.1 200 OK\r\n");
    // 必须要输出正确的 HTTP Headers 返回 JSON
    printf("Content-Type: application/json\r\n\r\n");

    // 从 GET 请求的环境变量中获取 target 参数
    char *query_string = getenv("QUERY_STRING");
    if (query_string == NULL) {
        printf("{\"status\": \"error\", \"message\": \"No query string provided\"}\n");
        return 0;
    }

    char target_encoded[256] = {0};
    char target[256] = {0};
    
    // 简单解析 QUERY_STRING 例如 target=8.8.8.8
    char *target_ptr = strstr(query_string, "target=");
    if (target_ptr) {
        target_ptr += 7; // 跳过 "target="
        int i = 0;
        while (target_ptr[i] != '&' && target_ptr[i] != '\0' && i < sizeof(target_encoded) - 1) {
            target_encoded[i] = target_ptr[i];
            i++;
        }
        target_encoded[i] = '\0';
        
        // URL 解码
        urldecode(target, target_encoded);
    }

    if (strlen(target) == 0) {
        printf("{\"status\": \"error\", \"message\": \"Target parameter is missing\"}\n");
        return 0;
    }

    // 防御型设计：使用定制的 eval() 执行
    // target 直接拼接到执行的字符串中，不做过滤，用底层的 execvp 方式执行
    char command[512];
    snprintf(command, sizeof(command), "ping -c 4 %s", target);

    char output[4096] = {0};
    int ret = eval(command, output, sizeof(output));

    if (ret != 0 && strlen(output) == 0) {
        printf("{\"status\": \"error\", \"message\": \"Failed to run ping command\"}\n");
        return 0;
    }

    // 将结果进行 JSON 转义以便在 Dashboard 正确显示
    char escaped_output[8192] = {0};
    int j = 0;
    for (int i = 0; output[i] != '\0' && j < sizeof(escaped_output) - 2; i++) {
        if (output[i] == '"') {
            escaped_output[j++] = '\\';
            escaped_output[j++] = '"';
        } else if (output[i] == '\n') {
            escaped_output[j++] = '\\';
            escaped_output[j++] = 'n';
        } else if (output[i] == '\r') {
            escaped_output[j++] = '\\';
            escaped_output[j++] = 'r';
        } else if (output[i] == '\t') {
            escaped_output[j++] = '\\';
            escaped_output[j++] = 't';
        } else if (output[i] == '\\') {
            escaped_output[j++] = '\\';
            escaped_output[j++] = '\\';
        } else {
            escaped_output[j++] = output[i];
        }
    }
    escaped_output[j] = '\0';

    // 将结果按照 Dashboard 要求的 JSON 格式输出给前端
    printf("{\"status\": \"success\", \"output\": \"%s\"}\n", escaped_output);

    return 0;
}