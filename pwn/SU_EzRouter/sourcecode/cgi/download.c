//download.cgi
#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <unistd.h>

// 引入上一层 www 目录下的 auth.h 进行鉴权校验
#include "../www/auth.h" 

#define BUF_SIZE 65536 // 64KB 更适合大文件

int main() {
  printf("HTTP/1.1 200 OK\r\n");
  // 核心！拦截未授权请求
  if (!check_auth()) {
      printf("Content-Type: application/json\r\n\r\n");
      printf("{\"status\": \"error\", \"message\": \"Access Denied: Please login first.\"}\n");
      return 0;
  }

  const char *file_path = "./FILE"; // 已经在 firmware 根目录下放置好了名为 FILE 的压缩包
  FILE *fp = fopen(file_path, "rb");

  if (!fp) {
    // 强制解析服务器端返回的 JSON
    printf("Content-Type: application/json\r\n\r\n");
    printf("{\"status\": \"error\", \"message\": \"Firmware backup file (FILE) not found on server.\"}\n");
    return 0;
  }

  /* 获取文件大小 */
  struct stat st;
  if (stat(file_path, &st) < 0) {
    fclose(fp);
    return 0;
  }

  /* HTTP Header */
  printf("Content-Type: application/octet-stream\r\n");
  printf("Content-Disposition: attachment; filename=\"FILE\"\r\n");
  printf("Content-Length: %lld\r\n", (long long)st.st_size);
  printf("\r\n"); // 这个双换行非常关键，代表头部结束，接下来吐出的二进制流绝对不能再经过 printf，只用 fwrite！

  /* 关闭 stdout 缓冲，提高传输效率，防止内存堆积 */
  setvbuf(stdout, NULL, _IONBF, 0);

  char buffer[BUF_SIZE];
  size_t n;

  // 使用二进制推流，这种性能极高
  while ((n = fread(buffer, 1, sizeof(buffer), fp)) > 0) {
    if (fwrite(buffer, 1, n, stdout) != n)
      break; // 如果浏览器主动停止下载，fwrite 返回值会不匹配，此时立刻打断循环，防止服务器死机
  }

  fclose(fp);
  return 0;
}
