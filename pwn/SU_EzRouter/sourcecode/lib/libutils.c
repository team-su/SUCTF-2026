#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/wait.h>
#include <ctype.h>
#include <string.h>
#include <sys/ipc.h>
#include <sys/msg.h>
#include "libutils.h"

#define IPC_KEY_CGI_TO_MAIN 0x33445566
#define IPC_KEY_MAIN_TO_CGI 0x66554433


void url_decode(char *str) {
    char *p = str;
    char *q = str;
    while (*p) {
        if (*p == '%' && p[1] && p[2]) {
            int hi = p[1];
            int lo = p[2];
            if (isxdigit(hi) && isxdigit(lo)) {
                hi = isdigit(hi) ? hi - '0' : (hi | 0x20) - 'a' + 10;
                lo = isdigit(lo) ? lo - '0' : (lo | 0x20) - 'a' + 10;
                *q++ = (hi << 4) | lo;
                p += 3;
                continue;
            }
        } else if (*p == '+') {
            *q++ = ' ';
            p++;
            continue;
        }
        *q++ = *p++;
    }
    *q = '\0';
}

// 安全的 eval 函数，虽然接收一整段命令字符串，但底层使用 execvp 执行
// 这样一来，类似 `ping -c 4 127.0.0.1; ls` 的传入，`; ls` 只会被当做 ping 的参数，而不是被 sh 解析为两条命令
int eval(const char *cmd, char *output_buf, size_t buf_size) {
    if (!cmd || !output_buf || buf_size == 0) return -1;
    
    // 拷贝一份命令用于字符串分割
    char cmd_copy[1024];
    strncpy(cmd_copy, cmd, sizeof(cmd_copy) - 1);
    cmd_copy[sizeof(cmd_copy) - 1] = '\0';

    char *args[64];
    int arg_count = 0;
    
    // 简单的按空格分割参数 (不处理引号包裹等复杂 shell 逻辑)
    char *token = strtok(cmd_copy, " \t");
    while (token != NULL && arg_count < 63) {
        args[arg_count++] = token;
        token = strtok(NULL, " \t");
    }
    args[arg_count] = NULL;
    
    if (arg_count == 0) return -1;

    int pipefd[2];
    if (pipe(pipefd) == -1) {
        return -1;
    }

    pid_t pid = fork();
    if (pid == -1) {
        close(pipefd[0]);
        close(pipefd[1]);
        return -1;
    }

    if (pid == 0) { // 子进程
        close(pipefd[0]);    // 关闭读端
        dup2(pipefd[1], STDOUT_FILENO); // 将标准输出重定向到管道写端
        dup2(pipefd[1], STDERR_FILENO); // 将错误输出也重定向
        
        execvp(args[0], args);
        
        // execvp 失败时退出
        perror("execvp");
        exit(1);
    } else { // 父进程
        close(pipefd[1]); // 关闭写端
        
        size_t total_read = 0;
        ssize_t n;
        while ((n = read(pipefd[0], output_buf + total_read, buf_size - total_read - 1)) > 0) {
            total_read += n;
            if (total_read >= buf_size - 1) {
                break;
            }
        }
        output_buf[total_read] = '\0';
        
        close(pipefd[0]);
        // 等待子进程结束
        int status;
        waitpid(pid, &status, 0);
        
        return WEXITSTATUS(status);
    }
}

int CFG_SET(long type_id, const void *data, size_t len) {
    if (type_id <= 0 || len > 4096) return -1;
    int msgid = msgget(IPC_KEY_CGI_TO_MAIN, 0666 | IPC_CREAT);
    if (msgid == -1) return -1;
    struct router_msgbuf msg;
    memset(&msg, 0, sizeof(msg));
    msg.mtype = type_id; 
    msg.data_len = len;
    if (data && len > 0) memcpy(msg.payload, data, len);
    return msgsnd(msgid, &msg, sizeof(struct router_msgbuf) - sizeof(long), 0);
}

int CFG_GET(long type_id, void *out_data, size_t max_len) {
    if (!out_data) return -1;
    int msgid = msgget(IPC_KEY_CGI_TO_MAIN, 0666 | IPC_CREAT);
    if (msgid == -1) return -1;
    if (type_id == 0 && max_len >= sizeof(struct router_msgbuf)) {
        return msgrcv(msgid, out_data, sizeof(struct router_msgbuf) - sizeof(long), 0, 0);
    }
    struct router_msgbuf msg;
    if (msgrcv(msgid, &msg, sizeof(struct router_msgbuf) - sizeof(long), type_id, 0) == -1) return -1;
    size_t copy_len = (msg.data_len > max_len) ? max_len : msg.data_len;
    memcpy(out_data, msg.payload, copy_len);
    return 0;
}

int IGP_SET(long type_id, const void *data, size_t len) {
    if (type_id <= 0 || len > 4096) return -1;
    int msgid = msgget(IPC_KEY_MAIN_TO_CGI, 0666 | IPC_CREAT);
    if (msgid == -1) return -1;
    struct router_msgbuf msg;
    memset(&msg, 0, sizeof(msg));
    msg.mtype = type_id; 
    msg.data_len = len;
    if (data && len > 0) memcpy(msg.payload, data, len);
    return msgsnd(msgid, &msg, sizeof(struct router_msgbuf) - sizeof(long), 0);
}

int IGP_GET(long type_id, void *out_data, size_t max_len) {
    if (!out_data || type_id <= 0) return -1;
    int msgid = msgget(IPC_KEY_MAIN_TO_CGI, 0666 | IPC_CREAT);
    if (msgid == -1) return -1;
    struct router_msgbuf msg;
    if (msgrcv(msgid, &msg, sizeof(struct router_msgbuf) - sizeof(long), type_id, 0) == -1) return -1;
    size_t copy_len = (msg.data_len > max_len) ? max_len : msg.data_len;
    memcpy(out_data, msg.payload, copy_len);
    return 0;
}
void log(char* msg)
{
    perror(msg);
}