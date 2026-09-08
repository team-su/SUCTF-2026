#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <unistd.h>

// 辅助函数：由于表单提交的数据是被 URL 编码过的，需要解码
void urldecode(char *dst, const char *src) {
  char a, b;
  while (*src) {
    if ((*src == '%') && ((a = src[1]) && (b = src[2])) &&
        (isxdigit(a) && isxdigit(b))) {
      if (a >= 'a')
        a -= 'a' - 'A';
      if (a >= 'A')
        a -= ('A' - 10);
      else
        a -= '0';
      if (b >= 'a')
        b -= 'a' - 'A';
      if (b >= 'A')
        b -= ('A' - 10);
      else
        b -= '0';
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
  // 获取请求方法
  char *method = getenv("REQUEST_METHOD");
  if (method == NULL || strcmp(method, "POST") != 0) {
    printf("Content-Type: text/html\r\n\r\n");
    printf("Error: Please use POST method.\n");
    return 0;
  }

  // 获取 POST 数据长度
  char *content_length_str = getenv("CONTENT_LENGTH");
  if (content_length_str == NULL) {
    printf("Content-Type: text/html\r\n\r\n");
    printf("Error: No Content-Length.\n");
    return 0;
  }

  int content_length = atoi(content_length_str);
  if (content_length <= 0) {
    printf("Content-Type: text/html\r\n\r\n");
    printf("Error: Empty content.\n");
    return 0;
  }

  char *post_data = (char *)malloc(content_length + 1);
  if (post_data == NULL) {
    printf("Content-Type: text/html\r\n\r\n");
    printf("Error: Memory allocation failed.\n");
    return 0;
  }

  fread(post_data, 1, content_length, stdin);
  post_data[content_length] = '\0';

  char username[256] = {0};
  char password[256] = {0};

  char *token = strtok(post_data, "&");
  while (token != NULL) {
    if (strncmp(token, "username=", 9) == 0) {
      urldecode(username, token + 9);
    } else if (strncmp(token, "password=", 9) == 0) {
      urldecode(password, token + 9);
    }
    token = strtok(NULL, "&");
  }

  free(post_data);
  
  // 开始输出 HTTP Header (注意不要立刻输出 \r\n\r\n)
  printf("HTTP/1.1 302 Found\r\n");
  printf("Content-Type: text/html\r\n");

  if (!strcmp(username, "normaluser") && !strcmp(password, "yhyyyyyyyyyyyhyhuityrscdn")) 
  {
    // 登录成功，重定向到 http 且 auth=1。Cookie 生成交由 /www/http 处理。
    printf("Location: /www/http?auth=1&action=login\r\n\r\n");
    // JS 兜底劫持跳转
    printf("<html><script>window.location.href='/www/http?auth=1&action=login';</script></html>\n");
  } else {
    // 登录失败，为了让 Bp 拦截到 预期带有 auth 的报文，这里也指向 http 且 auth=0
    printf("Location: /www/http?auth=0&action=login\r\n\r\n");
    // JS 兜底劫持跳转
    printf("<html><script>window.location.href='/www/http?auth=0&action=login';</script></html>\n");
  }
  return 0;
}
