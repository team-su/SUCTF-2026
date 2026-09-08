#ifndef HTTP_H
#define HTTP_H

#ifndef _GNU_SOURCE
#define _GNU_SOURCE
#endif

#include <arpa/inet.h>
#include <ctype.h>
#include <fcntl.h>
#include <limits.h>
#include <netinet/in.h>
#include <pthread.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <unistd.h>
#include <sys/time.h>
#include "../lib/libutils.h"

#define WEB_ROOT "./www"               
#define CGI_ROOT "./www/cgi-bin"        
#define BUFFER_SIZE 4096
#define MAX_HEADER_LEN 8192

/* 检查路径是否包含可疑的 ".." 段，防止遍历 */
static int contains_dot_dot(const char *path) {
    const char *p = path;
    while ((p = strstr(p, "..")) != NULL) {
        if ((p == path || *(p-1) == '/') && (*(p+2) == '/' || *(p+2) == '\0'))
            return 1;
        p += 2;
    }
    return 0;
}

/* 安全地构建绝对路径，并验证是否在根目录内 */
static int safe_path(const char *base, const char *request_path, char *result, size_t result_size) {
    char decoded[1024];
    strncpy(decoded, request_path, sizeof(decoded) - 1);
    decoded[sizeof(decoded) - 1] = '\0';

    // 截断查询参数，避免 realpath 失败
    char *q = strchr(decoded, '?');
    if (q) *q = '\0';

    url_decode(decoded);

    // 拒绝包含 ".." 段或非绝对路径的请求
    if (decoded[0] != '/' || contains_dot_dot(decoded))
        return 0;

    // 拼接完整路径
    char full[PATH_MAX];
    snprintf(full, sizeof(full), "%s%s", base, decoded);

    // 获取规范化物理路径（文件必须存在）
    char resolved[PATH_MAX];
    if (realpath(full, resolved) == NULL)
        return 0;

    char base_real[PATH_MAX];
    if (realpath(base, base_real) == NULL)
        return 0;

    size_t base_len = strlen(base_real);
    // 判断是否越权（前缀绕过防御）
    if (strncmp(resolved, base_real, base_len) != 0)
        return 0;
    
    // 强制要求前缀匹配的下一个字符必须是路径分隔符或字符串结尾，防止以文件名作为前缀绕过（如 base="/www", user="/www_flag"）
    if (resolved[base_len] != '/' && resolved[base_len] != '\0') {
        return 0;
    }

    strncpy(result, resolved, result_size);
    result[result_size - 1] = '\0';
    return 1;
}

/* 发送错误页面 */
static void send_error(int sock, int code, const char *message) {
    char buf[256];
    snprintf(buf, sizeof(buf),
             "HTTP/1.1 %d %s\r\n"
             "Content-Type: text/plain\r\n"
             "Content-Length: %zu\r\n"
             "Connection: close\r\n"
             "\r\n"
             "%s",
             code, message, strlen(message), message);
    send(sock, buf, strlen(buf), 0);
}

/* 提取 HTTP Header 值 */
static void get_header_value(const char *request, const char *header_name, char *output, size_t out_size) {
    output[0] = '\0';
    char search[128];
    snprintf(search, sizeof(search), "\r\n%s:", header_name);
    
    const char *p = strcasestr(request, search);
    if (!p) {
        // 适配可能是第一行后的直接 \n 而非 \r\n (非标环境)
        snprintf(search, sizeof(search), "\n%s:", header_name);
        p = strcasestr(request, search);
        if (p) p += 1;
    } else {
        p += 2;
    }

    if (p) {
        p += strlen(header_name) + 1; // skip header_name and colon
        while (*p == ' ' || *p == '\t') p++; // skip whitespace
        const char *end = strpbrk(p, "\r\n");
        if (end) {
            size_t len = end - p;
            if (len >= out_size) len = out_size - 1;
            strncpy(output, p, len);
            output[len] = '\0';
        }
    }
}

/* 处理静态请求 */
static void handle_static(int client_sock, const char *request_path, const char *buffer) {
    char safe_path_str[PATH_MAX];
    char target_path[1024];
    strncpy(target_path, request_path, sizeof(target_path)-1);
    target_path[sizeof(target_path)-1] = '\0';

    // 默认主页
    if (strcmp(target_path, "/") == 0) {
        strcpy(target_path, "/index.html");
    }

    if (!safe_path(WEB_ROOT, target_path, safe_path_str, sizeof(safe_path_str))) {
        send_error(client_sock, 403, "Forbidden");
        return;
    }

    // --- 权限校验逻辑开始 ---
    // 保护控制台相关页面
    if (strstr(target_path, "/control.html")) {
        char cookie_header[1024] = "";
        char session_id[128] = "";
        int authorized = 0;

        // 从请求头获取 Cookie
        get_header_value(buffer, "Cookie", cookie_header, sizeof(cookie_header));
        
        // 简单提取 session_id=xxx
        char *sess_ptr = strstr(cookie_header, "session_id=");
        if (sess_ptr) {
            sess_ptr += 11; // 跳过 "session_id="
            int i = 0;
            while (sess_ptr[i] != ';' && sess_ptr[i] != '\r' && sess_ptr[i] != '\n' && sess_ptr[i] != '\0' && i < sizeof(session_id) - 1) {
                session_id[i] = sess_ptr[i];
                i++;
            }
            session_id[i] = '\0';

            // 检查 session 文件是否存在
            if (strlen(session_id) > 0) {
                char session_file[256];
                snprintf(session_file, sizeof(session_file), "./tmp/sessions/%s", session_id);
                if (access(session_file, F_OK) == 0) {
                    authorized = 1; // 文件存在，授权通过
                }
            }
        }

        if (!authorized) {
            // 没有权限，将其重定向回登录页面
            const char *resp = "HTTP/1.1 302 Found\r\n"
                               "Location: /index.html\r\n\r\n";
            send(client_sock, resp, strlen(resp), 0);
            return;
        }
    }
    // --- 权限校验逻辑结束 ---

    struct stat st;
    if (stat(safe_path_str, &st) == 0 && S_ISDIR(st.st_mode)) {
        size_t len = strlen(safe_path_str);
        if (safe_path_str[len - 1] != '/') strcat(safe_path_str, "/");
        strcat(safe_path_str, "index.html");
    }

    FILE *fp = fopen(safe_path_str, "rb");
    if (!fp) {
        send_error(client_sock, 404, "Not Found");
        return;
    }

    const char *ext = strrchr(safe_path_str, '.');
    const char *mime = "application/octet-stream";
    if (ext) {
        if (strcmp(ext, ".html") == 0 || strcmp(ext, ".htm") == 0) mime = "text/html";
        else if (strcmp(ext, ".css") == 0) mime = "text/css";
        else if (strcmp(ext, ".js") == 0) mime = "application/javascript";
        else if (strcmp(ext, ".png") == 0) mime = "image/png";
        else if (strcmp(ext, ".jpg") == 0 || strcmp(ext, ".jpeg") == 0) mime = "image/jpeg";
        else if (strcmp(ext, ".gif") == 0) mime = "image/gif";
    }

    char header[256];
    snprintf(header, sizeof(header),
             "HTTP/1.1 200 OK\r\n"
             "Content-Type: %s\r\n"
             "Connection: close\r\n"
             "\r\n",
             mime);
    send(client_sock, header, strlen(header), 0);

    char fbuf[BUFFER_SIZE];
    size_t n;
    while ((n = fread(fbuf, 1, sizeof(fbuf), fp)) > 0) {
        if (send(client_sock, fbuf, n, 0) != (ssize_t)n) break;
    }
    fclose(fp);
}

/* 处理 CGI 请求 */
static void handle_cgi(int client_sock, const char *method, const char *request_path, const char *buffer, int bytes_read) {
    char script_path[PATH_MAX];
    char pure_path[1024];
    strncpy(pure_path, request_path, sizeof(pure_path)-1);
    pure_path[sizeof(pure_path)-1] = '\0';
    
    char *q_ptr = strchr(pure_path, '?');
    char query_string[1024] = "";
    if (q_ptr) {
        *q_ptr = '\0';
        strncpy(query_string, q_ptr + 1, sizeof(query_string)-1);
    }

    // 剔除 /cgi-bin 前缀再做安全寻址 (假设 CGI 存在于 ./www/cgi-bin 并且请求形式为 /cgi-bin/some.cgi)
    const char *sub_path = pure_path;
    if (strncmp(sub_path, "/cgi-bin", 8) == 0) {
        sub_path += 8;
        if (*sub_path == '\0') sub_path = "/";
    }

    if (!safe_path(CGI_ROOT, sub_path, script_path, sizeof(script_path))) {
        send_error(client_sock, 403, "Forbidden");
        return;
    }

    struct stat st;
    if (stat(script_path, &st) != 0 || !(st.st_mode & S_IXUSR)) {
        send_error(client_sock, 403, "CGI script not executable");
        return;
    }

    char content_length_str[64] = "0";
    char content_type[256] = "";
    char cookie[1024] = "";
    
    get_header_value(buffer, "Content-Length", content_length_str, sizeof(content_length_str));
    get_header_value(buffer, "Content-Type", content_type, sizeof(content_type));
    get_header_value(buffer, "Cookie", cookie, sizeof(cookie));

    int content_length = atoi(content_length_str);

    const char *body = strstr(buffer, "\r\n\r\n");
    if (body) body += 4;

    int pipein[2];
    if (pipe(pipein) < 0) {
        send_error(client_sock, 500, "Internal Server Error");
        return;
    }

    pid_t pid = fork();
    if (pid == 0) {
        // 子进程
        close(pipein[1]);
        dup2(pipein[0], STDIN_FILENO);
        dup2(client_sock, STDOUT_FILENO); 

        char env_method[128], env_cl[128], env_ct[512], env_cookie[2048], env_query[2048], env_script[PATH_MAX+64];
        snprintf(env_method, sizeof(env_method), "REQUEST_METHOD=%s", method);
        snprintf(env_cl, sizeof(env_cl), "CONTENT_LENGTH=%d", content_length);
        snprintf(env_ct, sizeof(env_ct), "CONTENT_TYPE=%s", content_type);
        snprintf(env_cookie, sizeof(env_cookie), "HTTP_COOKIE=%s", cookie);
        snprintf(env_query, sizeof(env_query), "QUERY_STRING=%s", query_string);
        snprintf(env_script, sizeof(env_script), "SCRIPT_FILENAME=%s", script_path);

        char *envp[] = {
            env_method, env_cl, env_ct, env_cookie, env_query, env_script,
            "SERVER_PROTOCOL=HTTP/1.1", "GATEWAY_INTERFACE=CGI/1.1",
            "PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin", NULL
        };

        execve(script_path, (char *[]){script_path, NULL}, envp);
        exit(1);
    } else if (pid > 0) {
        // 父进程
        close(pipein[0]);

        // 不再由后端 Server 预设 200 OK，由各 CGI 自行输出状态码第一行 (如 HTTP/1.1 200 OK 或 302 Found)
        // 这样可以确保 Burp Suite 等工具能正确识别重定向。

        if (body && content_length > 0) {
            int body_in_buffer = bytes_read - (body - buffer);
            if (body_in_buffer > 0) write(pipein[1], body, body_in_buffer);
            int remaining = content_length - body_in_buffer;
            while (remaining > 0) {
                char temp[BUFFER_SIZE];
                int r = recv(client_sock, temp, sizeof(temp), 0);
                if (r <= 0) break;
                write(pipein[1], temp, r);
                remaining -= r;
            }
        }
        close(pipein[1]);
        waitpid(pid, NULL, 0);
    } else {
        close(pipein[0]);
        close(pipein[1]);
        send_error(client_sock, 500, "Internal Server Error");
    }
}

/* 处理业务逻辑跳转，生成 Cookie 等 */
static void handle_action(int client_sock, const char *request_path) {
    char pure_path[1024];
    strncpy(pure_path, request_path, sizeof(pure_path)-1);
    pure_path[sizeof(pure_path)-1] = '\0';
    
    char *q_ptr = strchr(pure_path, '?');
    char query_string[1024] = "";
    if (q_ptr) {
        *q_ptr = '\0';
        strncpy(query_string, q_ptr + 1, sizeof(query_string)-1);
    }
    
    if (strstr(query_string, "action=login")) {
        if (strstr(query_string, "auth=1")) {
            // 登录成功，生成 Session ID (简单的随机数)
            srand(time(NULL) ^ getpid());
            char session_id[64];
            snprintf(session_id, sizeof(session_id), "%08x%08x%08x%08x", rand(), rand(), rand(), rand());
            
            // 将 Session 存储在服务器的 tmp/sessions 目录下
            char session_file[256];
            snprintf(session_file, sizeof(session_file), "./tmp/sessions/%s", session_id);
            FILE *sf = fopen(session_file, "w");
            if (sf) {
                fprintf(sf, "normaluser"); 
                fclose(sf);
            }
            
            char resp[1024];
            snprintf(resp, sizeof(resp), 
                     "HTTP/1.1 302 Found\r\n"
                     "Set-Cookie: session_id=%s; Path=/; HttpOnly\r\n"
                     "Location: /control.html\r\n\r\n", 
                     session_id);
            send(client_sock, resp, strlen(resp), 0);
        } else {
            // 登录失败
            const char *resp = "HTTP/1.1 302 Found\r\n"
                               "Location: /index.html\r\n\r\n";
            send(client_sock, resp, strlen(resp), 0);
        }
    } else {
        send_error(client_sock, 400, "Bad Request");
    }
}

/* 分发请求 */
static void dispatch_request(int client_sock, const char *method, const char *request_path, const char *buffer, int bytes_read) {
    if (strcmp(method, "GET") != 0 && strcmp(method, "POST") != 0) {
        send_error(client_sock, 405, "Method Not Allowed");
        return;
    }

    if (strncmp(request_path, "/www/http", 9) == 0) {
        handle_action(client_sock, request_path);
    } else if (strncmp(request_path, "/cgi-bin/", 9) == 0) {
        handle_cgi(client_sock, method, request_path, buffer, bytes_read);
    } else {
        handle_static(client_sock, request_path, buffer);
    }
}

/* 客户端处理线程 */
static void *handle_client(void *arg) {
    int client_sock = *(int *)arg;
    free(arg);

    // 设置收发超时，防止 Slowloris DoS
    struct timeval timeout;
    timeout.tv_sec = 5; 
    timeout.tv_usec = 0;
    setsockopt(client_sock, SOL_SOCKET, SO_RCVTIMEO, &timeout, sizeof(timeout));
    setsockopt(client_sock, SOL_SOCKET, SO_SNDTIMEO, &timeout, sizeof(timeout));

    char buffer[MAX_HEADER_LEN];
    int bytes_read = recv(client_sock, buffer, sizeof(buffer) - 1, 0);
    if (bytes_read <= 0) {
        close(client_sock);
        return NULL;
    }
    buffer[bytes_read] = '\0';

    char method[16] = {0}, path[1024] = {0}, protocol[16] = {0};
    if (sscanf(buffer, "%15s %1023s %15s", method, path, protocol) != 3) {
        send_error(client_sock, 400, "Bad Request");
        close(client_sock);
        return NULL;
    }

    dispatch_request(client_sock, method, path, buffer, bytes_read);
    close(client_sock);
    return NULL;
}

#endif // HTTP_H