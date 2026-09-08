#include <arpa/inet.h>
#include <event2/buffer.h>
#include <event2/bufferevent.h>
#include <event2/event.h>
#include <event2/listener.h>
#include <seccomp.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/prctl.h>
#include <unistd.h>

#define MAX_SESSIONS 2
#define USER_BUF_SIZE 32

typedef enum { PROTO_UDP = 0, PROTO_TCP = 1 } proto_t;

struct session {
    char user_buf[USER_BUF_SIZE];
    proto_t type;
    struct bufferevent *bev;
    int udp_fd;
};

struct response {
    struct sockaddr sa;
    char hostname[64];
};

struct session g_sessions[MAX_SESSIONS];
struct event_base *base;

static void setup_sandbox(void) {
    scmp_filter_ctx ctx = seccomp_init(SCMP_ACT_ALLOW);
    if (ctx == NULL) {
        return;
    }

    if (prctl(PR_SET_NO_NEW_PRIVS, 1, 0, 0, 0) < 0) {
        seccomp_release(ctx);
        return;
    }

    seccomp_rule_add(ctx, SCMP_ACT_KILL, SCMP_SYS(execve), 0);
    seccomp_rule_add(ctx, SCMP_ACT_KILL, SCMP_SYS(execveat), 0);

    if (seccomp_load(ctx) < 0) {
        seccomp_release(ctx);
        return;
    }

    seccomp_release(ctx);
}

static void cleanup_response(const void *data, size_t datalen, void *extra) {
    (void)datalen;
    (void)extra;
    free((void *)data);
}

static void handle_business(
    struct session *s,
    char *temp_recv,
    int n,
    struct sockaddr_in *udp_client_addr
) {
    if (n <= 0) {
        return;
    }
    temp_recv[n] = '\0';

    char local_hostname[64];
    struct response *resp = malloc(sizeof(struct response));
    if (!resp) {
        return;
    }
    memset(resp, 0, sizeof(struct response));

    struct sockaddr_in *sin = (struct sockaddr_in *)&resp->sa;

    if (inet_pton(AF_INET, temp_recv, &sin->sin_addr) != 0) {
        sin->sin_family = AF_INET;
        gethostname(local_hostname, sizeof(local_hostname));
        memcpy(resp->hostname, local_hostname, sizeof(resp->hostname));

        memcpy(s->user_buf, temp_recv, n);

        if (s->type == PROTO_TCP && s->bev) {
            struct evbuffer *output = bufferevent_get_output(s->bev);
            evbuffer_add_reference(output, resp, sizeof(struct response), cleanup_response, NULL);
        } else {
            sendto(
                s->udp_fd,
                resp,
                sizeof(struct response),
                0,
                (struct sockaddr *)udp_client_addr,
                sizeof(*udp_client_addr)
            );
            free(resp);
        }
    } else {
        free(resp);
    }
}

static void tcp_read_cb(struct bufferevent *bev, void *arg) {
    struct session *s = (struct session *)arg;
    char temp_recv[1024];
    int n = bufferevent_read(bev, temp_recv, sizeof(temp_recv) - 1);
    handle_business(s, temp_recv, n, NULL);
}

static void udp_event_cb(evutil_socket_t fd, short events, void *arg) {
    (void)events;
    struct session *s = (struct session *)arg;
    struct sockaddr_in client_addr;
    socklen_t addr_len = sizeof(client_addr);
    char temp_recv[1024];
    int n = recvfrom(
        fd,
        temp_recv,
        sizeof(temp_recv) - 1,
        0,
        (struct sockaddr *)&client_addr,
        &addr_len
    );
    handle_business(s, temp_recv, n, &client_addr);
}

static void accept_conn_cb(
    struct evconnlistener *listener,
    evutil_socket_t fd,
    struct sockaddr *address,
    int socklen,
    void *arg
) {
    (void)listener;
    (void)address;
    (void)socklen;
    (void)arg;
    struct session *s = &g_sessions[1];
    s->type = PROTO_TCP;
    s->bev = bufferevent_socket_new(base, fd, BEV_OPT_CLOSE_ON_FREE);
    bufferevent_setcb(s->bev, tcp_read_cb, NULL, NULL, s);
    bufferevent_enable(s->bev, EV_READ | EV_WRITE);
}

int main(void) {
    setup_sandbox();
    base = event_base_new();
    memset(g_sessions, 0, sizeof(g_sessions));

    int udp_fd = socket(AF_INET, SOCK_DGRAM, 0);
    struct sockaddr_in sin_udp = {.sin_family = AF_INET, .sin_port = htons(8889)};
    bind(udp_fd, (struct sockaddr *)&sin_udp, sizeof(sin_udp));

    g_sessions[0].type = PROTO_UDP;
    g_sessions[0].udp_fd = udp_fd;
    struct event *udp_ev = event_new(base, udp_fd, EV_READ | EV_PERSIST, udp_event_cb, &g_sessions[0]);
    event_add(udp_ev, NULL);

    struct sockaddr_in sin_tcp = {.sin_family = AF_INET, .sin_port = htons(8888)};
    struct evconnlistener *listener = evconnlistener_new_bind(
        base,
        accept_conn_cb,
        NULL,
        LEV_OPT_REUSEABLE | LEV_OPT_CLOSE_ON_FREE,
        -1,
        (struct sockaddr *)&sin_tcp,
        sizeof(sin_tcp)
    );
    (void)listener;

    event_base_dispatch(base);
    return 0;
}
