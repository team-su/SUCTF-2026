#ifndef LIBUTILS_H
#define LIBUTILS_H

#include <sys/types.h>

// 定义路由器内部通信的消息结构（允许携带大容量数据字段）
struct router_msgbuf {
    long mtype;           // 消息通道类型 (直接使用传入的 type_id)
    int data_len;         // 实际数据载荷的长度
    char payload[4096];   // 数据载荷 (容易出现溢出的经典缓冲区)
};

// 简单的 URL 解码（原地操作）
void url_decode(char *str);

// 安全的 eval 执行函数
int eval(const char *cmd, char *output_buf, size_t buf_size);

// 进程间通信相关函数
// CFG 系列 (CGI_TO_MAIN 队列)
int CFG_SET(long type_id, const void *data, size_t len);       // 发送请求
int CFG_GET(long type_id, void *out_data, size_t max_len);     // 接收请求

// IGP 系列 (MAIN_TO_CGI 队列)
int IGP_SET(long type_id, const void *data, size_t len);       // 发送响应
int IGP_GET(long type_id, void *out_data, size_t max_len);     // 接收响应
void log(char* msg);
#endif // LIBUTILS_H
