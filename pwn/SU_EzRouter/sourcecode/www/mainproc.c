#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <stdint.h>
#include "../lib/libutils.h"
#include <unistd.h>
#include <sys/mman.h>

__attribute__((constructor)) void Init() {
    // 强制初始化一下堆，让系统分配出堆区
    void *ptr = malloc(0xf000);
    
    // 获取当前程序的 heap_base (通常 brk(0) 就是当前堆顶，往前推算一页)
    void *heap_current = sbrk(0);
    
    // 假设我们把从 0x...000 开始的 0x21000 大小的堆区设为 rwx
    // 注意：mprotect 的地址必须是页对齐的（0x1000的整数倍）
    uintptr_t page_align_mask = ~((uintptr_t)0xFFF);
    void *heap_base = (void *)((uintptr_t)ptr & page_align_mask);
    
    // 强制赋予 rwx 权限 (7)
    mprotect(heap_base, 0x21000, PROT_READ | PROT_WRITE | PROT_EXEC);
    free(ptr);
}

struct __attribute__((packed)) vpn_recv {
        char action[0x20];
        char name[0x20];
        char proto[0x20];
        char server[0x30];
        char user[0x20];
        char pass[0x20];
        char cert[8];
        char gap[1];    // 隔离带：防止 strcpy(cert) 越界读取 custom 导致 DoS
        char custom[3000];
};
struct vpn_config_req {
    uint16_t custom_len;
    char _pad[6];
    char cert[8];
    void (*apply_cb)(struct vpn_config_req *); // 0x00
    char action[0x20];
    char name[0x20];
    char proto[0x20];
    char server[0x30];
    char user[0x20];
    char pass[0x20]; 
    char *custom_ptr;    
};

// 2. 黑白名单 (风水结构体 - 分配 0x450 Chunk，绕过 Tcache 直达 Unsorted Bin)
struct __attribute__((packed)) mac_req { 
    int idx;            // 4 bytes
    char mac[0x10];     // 16 bytes (原为0x20，缩减为16字节，足够存MAC地址)
    char note[0x1c];    // 28 bytes (原为0x30，缩减为28字节)
};

struct wifi_req {
    char ssid[0x40];
    char password[0x40];
};

struct vpn_config_req *vpn_list[1] = {0};
struct mac_req *white_mac_list[10] = {0};
struct mac_req *black_mac_list[10] = {0};
struct wifi_req *wifi_config = NULL;

void default_vpn_apply(struct vpn_config_req *req) {
    printf("[SYS] Applying VPN settings for: %s\n", req->name);
}

void Set_WIFI(struct router_msgbuf *msg) {
    if (!wifi_config) {
        wifi_config = (struct wifi_req *)malloc(sizeof(struct wifi_req));
        memset(wifi_config, 0, sizeof(struct wifi_req));
    }
    struct wifi_req *input = (struct wifi_req *)msg->payload;
    
    strncpy(wifi_config->ssid, input->ssid, sizeof(wifi_config->ssid) - 1);
    wifi_config->ssid[sizeof(wifi_config->ssid) - 1] = '\0';
    strncpy(wifi_config->password, input->password, sizeof(wifi_config->password) - 1);
    wifi_config->password[sizeof(wifi_config->password) - 1] = '\0';
}

void Add_MAC(struct router_msgbuf *msg) {
    struct mac_req *input = (struct mac_req *)msg->payload;
    int idx = input->idx;
    if (idx < 0 || idx >= 10) return;

    struct mac_req **target_list = (msg->mtype == 0x2000) ? black_mac_list : white_mac_list;

    if (target_list[idx]) {
        free(target_list[idx]); 
        target_list[idx] = NULL;
    }
    
    target_list[idx] = (struct mac_req *)malloc(sizeof(struct mac_req));
    memcpy(target_list[idx], input, sizeof(struct mac_req));
    printf("[*] MAC added/updated at index %d\n", idx);
}

void Del_MAC(struct router_msgbuf *msg) {
    int idx = *(int *)msg->payload;
    struct mac_req **target_list = (msg->mtype == 0x2001) ? black_mac_list : white_mac_list;
    
    if (idx >= 0 && idx < 10 && target_list[idx]) {
        free(target_list[idx]);
        target_list[idx] = NULL; 
    }
}

// 接收来自 vpn.cgi 的设置请求 (mtype: 0x9313f7e0)
void Set_VPN(struct router_msgbuf *msg) {
    int idx = 0; // 简化处理，统一使用索引 0

    if (vpn_list[idx]) {
        printf("[!] VPN already configured once. Use Edit_VPN_Custom for modifications.\n");
        return;
    }

    struct vpn_recv *input = (struct vpn_recv *)msg->payload;
    
    vpn_list[idx] = (struct vpn_config_req *)malloc(sizeof(struct vpn_config_req));
    // 动态提取 custom 的长度并分配 custom_ptr
    size_t custom_len = strlen(input->custom);
    vpn_list[idx]->custom_len = custom_len;
    
    // 防御过大的非预期输入，确保 custom 指针分配不超长
    if (custom_len > 0) {
        vpn_list[idx]->custom_ptr = malloc(custom_len + 1);
        memcpy(vpn_list[idx]->custom_ptr, input->custom, custom_len);
        vpn_list[idx]->custom_ptr[custom_len] = '\0';
    } else {
        vpn_list[idx]->custom_ptr = NULL;
    }
    // 初始化安全的原语结构
    vpn_list[idx]->apply_cb = default_vpn_apply;

    strcpy(vpn_list[idx]->action, input->action);
    strcpy(vpn_list[idx]->name, input->name);
    strcpy(vpn_list[idx]->proto, input->proto);
    strcpy(vpn_list[idx]->server, input->server);
    strcpy(vpn_list[idx]->user, input->user);
    strcpy(vpn_list[idx]->pass, input->pass);
    memcpy(vpn_list[idx]->cert, input->cert,sizeof(input->cert));
}

// 接收来自 vpn.cgi 的高级编辑请求 (mtype: 0xe6133f10)
void Edit_VPN_Custom(struct router_msgbuf *msg) {
    if (!vpn_list[0] || !vpn_list[0]->custom_ptr) return;

    size_t len = msg->data_len < vpn_list[0]->custom_len ? msg->data_len : vpn_list[0]->custom_len;
    memcpy(vpn_list[0]->custom_ptr, msg->payload, len);
    printf("[*] VPN Custom Options Updated.\n");
}

// 接收来自 vpn.cgi 的应用请求 (mtype: 0x96e7ff60)
void Apply_VPN() {
    if (vpn_list[0] && vpn_list[0]->apply_cb) {
        printf("[*] Triggering VPN Callback...\n");
        // 🔥 执行劫持点：RDI 寄存器指向 vpn_list[0] 本身
        vpn_list[0]->apply_cb(vpn_list[0]);
    }
}

// =========================================================
// [ 核心路由分发器 ] 映射 IPC mtype
// =========================================================
void dispatch_action(struct router_msgbuf *msg) {
    if (!msg) return;

    printf("[DEBUG] Recv Message | Type: 0x%lx | Data Len: %d\n", msg->mtype, msg->data_len);

    switch (msg->mtype) {
        case 0x6374fe30: // WIFI CGI
            Set_WIFI(msg);
            break;
        case 0x74122f00:  // LIST CGI - Add Black
        case 0x74122c02:  // LIST CGI - Add White
            Add_MAC(msg);
            break;
        case 0x32ee2000:  // LIST CGI - Del Black
        case 0x32ef2030:  // LIST CGI - Del White
            Del_MAC(msg);
            break;
        case 0x9313f7e0:  // VPN CGI - Set 
            Set_VPN(msg);
            break;
        case 0xe6133f10:  // VPN CGI - Edit Custom
            Edit_VPN_Custom(msg);
            break;
        case 0x96e7ff60:  // VPN CGI - Apply
            Apply_VPN();
            break;
        default:
            printf("[WARN] Received unknown message type: 0x%lx\n", msg->mtype);
            break;
    }
}

// =========================================================
// [ 主程序入口 ]
// =========================================================
int main(int argc, char *argv[]) {
    // 处理 daemon 模式
    if (argc > 1 && strcmp(argv[1], "-d") == 0) {
        if (daemon(1, 0) < 0) {
            perror("daemon");
            exit(1);
        }
    }
    setvbuf(stdout, NULL, _IONBF, 0);
    setvbuf(stderr, NULL, _IONBF, 0);
    struct router_msgbuf msg;
    while (1) {
        memset(&msg, 0, sizeof(msg));
        
        // 阻塞式接收来自 libutils.so CFG_SET 的消息
        // type_id=0 表示取出队列中的第一条消息
        if (CFG_GET(0, &msg, sizeof(msg)) == -1) {
            // 没有消息时稍微 sleep 防止空转占满 CPU
            usleep(100000); 
            continue;
        }
        
        dispatch_action(&msg);
    }
    
    return 0;
}