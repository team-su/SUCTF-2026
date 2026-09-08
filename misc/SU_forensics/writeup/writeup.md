## SU_forensics-WP

首先给出的镜像为AD1文件，经过简答搜索可以得知这个是FTK imager生成的镜像文件，使用FTK进行取证

![image-20260306220811626](./img/image-20260306220811626.png)

### 1.设备上次关闭时间是什么时候？请以 UTC+8 时区提供您的答案。（YYYY/MM/DDTHH:MM:SS） 

第一个题目是设备上次的关闭时间，导出C:\Windows\System32\config\SYSTEM查看SYSTEM注册表文件即可（使用工具或者010手动查看都可）

![image-20260306221337601](./img/image-20260306221337601.png)

```
2026/03/05T17:23:06
```

### 2.记事本删除内容的MD5值(32位小写)。

由题目描述可以得知，嫌疑人在编辑一个记事本文件，“页面空白，显示有未保存更改，警方进入时，他快速按了几下键盘，疑似删除了其中的关键内容”

搜索notepad forensics大概可以找到相关知识：

[内存取证 - Windows 记事本第一部分 | ogmini - DFIR 探索 --- Memory Forensics - Windows Notepad Part 1 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/08/13/Memory-Forensics-Windows-Notepad-Part-1.html)

[内存取证 - Windows 记事本第 2 部分 | ogmini - DFIR 探索 --- Memory Forensics - Windows Notepad Part 2 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/08/14/Memory-Forensics-Windows-Notepad-Part-2.html)

[内存取证 - Windows 记事本第 3 部分 | ogmini - DFIR 探索 --- Memory Forensics - Windows Notepad Part 3 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/08/15/Memory-Forensics-Windows-Notepad-Part-3.html)

[Memory Forensics - Windows Notepad Part 4 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/08/16/Memory-Forensics-Windows-Notepad-Part-4.html)

[内存取证 - Windows 记事本第 5 部分 | ogmini - DFIR 探索 --- Memory Forensics - Windows Notepad Part 5 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/08/29/Memory-Forensics-Windows-Notepad-Part-5.html)

[内存取证 - Windows 记事本第 6 部分 | ogmini - DFIR 探索 --- Memory Forensics - Windows Notepad Part 6 | ogmini - Exploration of DFIR](https://ogmini.github.io/2025/09/06/Memory-Forensics-Windows-Notepad-Part-6.html)

然后可以根据作者的仓库的工具恢复记事内容

[ogmini/Notepad-State-Library: 用于 Windows 11 Notepad 状态文件的 C# 库和研究笔记 --- ogmini/Notepad-State-Library: C# Library and research notes for Windows 11 Notepad State Files](https://github.com/ogmini/Notepad-State-Library)

找到%localappdata%\Packages\Microsoft.WindowsNotepad_8wekyb3d8bbwe\LocalState\TabState对应的文件

![image-20260306222543277](./img/image-20260306222543277.png)

![image-20260306222706752](./img/image-20260306222706752.png)

根据这个跑出来的缓冲区块的文件，可以恢复出文件内容

```
Key instructions:
1.Key must not be entirely stored on disk.
2.The key has four parts
3.The Key requires reshuffling order:1-4-3-2
4.There is a key generted by AI
complete
```

md5得到(需要注意换行符为0d)

![image-20260306223656440](./img/image-20260315125024716.png)

```
c1c4c50f51afc97a58385457af43e169
```

### 3.第一密钥是什么？

可以分析得知该电脑一共有utools，ollama，cherrystudio这三款额外下载的应用，做题时可以对其进行一一分析，对utools分析时，可以得知其有粘贴板，可能会藏有信息

![image-20260307141158490](./img/image-20260307141158490.png)

参考[uTools剪贴板取证思路 - WXjzc - 博客园](https://www.cnblogs.com/WXjzc/p/18129696)及[分析 uTools v6.1.0 完整性校验 - 吾爱破解 - 52pojie.cn](https://www.52pojie.cn/thread-2003165-1-1.html)

![image-20260306232635773](./img/image-20260306232635773.png)

得知这是一个aes解密函数，模式是AES-252-CBC，IV是UTOOLS0123456789，密钥由W().getLocalSecretKey()生成

![image-20260306232748520](./img/image-20260306232748520.png)



W又是通过引入addon来创建的，通过`app/node_modules/addon/index.js`得知会加载`app.asar.unpacked/node_modules/addon/win32-x64.node`文件

![image-20260307131722261](./img/image-20260307131722261.png)

我们可以对win32-x64.node进行逆向分析

对字符串进行搜索找到getLocalSecretKey

![image-20260307132246425](./img/image-20260307132246425.png)

找到对应函数

![image-20260307133640791](./img/image-20260307133640791.png)

在该函数中 N-API 导出属性被注册，`getLocalSecretKey` 对应回调为:`sub_180006850`

![image-20260307134538804](./img/image-20260307134538804.png)

在6850中调用napi_create_string_utf8(...)，参数为v3=qword_1800B1DD0，寻找其初始化

![image-20260307134631777](./img/image-20260307134631777.png)sub_180005510调用qword_1800B1DD0，

````
__int64 *sub_180005510()
{
  int v0; // edx
  __m128i *v1; // rcx
  bool v2; // zf
  int v3; // edx
  __m128i *p_si128; // rax
  __int64 v5; // rax
  __int64 v6; // r8
  __int64 v7; // rax
  __int128 *v8; // rdx
  void *v9; // rcx
  __int128 v11; // [rsp+38h] [rbp-21h] BYREF
  __int128 v12; // [rsp+48h] [rbp-11h]
  _BYTE v13[32]; // [rsp+58h] [rbp-1h] BYREF
  __m128i v14; // [rsp+78h] [rbp+1Fh] BYREF
  int v15; // [rsp+88h] [rbp+2Fh]
  char v16; // [rsp+8Ch] [rbp+33h]
  __m128i si128; // [rsp+90h] [rbp+37h] BYREF
  int v18; // [rsp+A0h] [rbp+47h]
  char v19; // [rsp+A4h] [rbp+4Bh]

  v18 = '1\x10\x04"';
  v19 = 'W';
  v15 = 'G\x13F*';
  v16 = 'w';
  si128 = _mm_load_si128((const __m128i *)&xmmword_1800A3130);
  v14 = _mm_load_si128((const __m128i *)&xmmword_1800A3090);
  if ( !dword_1800B1D70 )
  {
    v0 = 99;
    v14.m128i_i8[0] = 124;
    v1 = &v14;
    do
    {
      v1 = (__m128i *)((char *)v1 + 1);
      v2 = (unsigned __int8)++v0 == v1->m128i_i8[0];
      v1->m128i_i8[0] ^= v0;
    }
    while ( !v2 );
    v3 = 67;
    si128.m128i_i8[0] = 72;
    p_si128 = &si128;
    do
    {
      p_si128 = (__m128i *)((char *)p_si128 + 1);
      v2 = (unsigned __int8)++v3 == p_si128->m128i_i8[0];
      p_si128->m128i_i8[0] ^= v3;
    }
    while ( !v2 );
  }
  v5 = sub_18001CC10(v13, &si128, &pbInput);
  v6 = -1;
  do
    ++v6;
  while ( v14.m128i_i8[v6] );
  v7 = sub_180021A10(v5, &v14, v6);
  v11 = 0;
  v12 = 0;
  v11 = *(_OWORD *)v7;
  v12 = *(_OWORD *)(v7 + 16);
  *(_QWORD *)(v7 + 16) = 0;
  *(_QWORD *)(v7 + 24) = 15;
  *(_BYTE *)v7 = 0;
  sub_1800216F0(v13);
  v8 = &v11;
  if ( *((_QWORD *)&v12 + 1) > 0xFu )
    v8 = (__int128 *)v11;
  sub_180004300(&qword_1800B1DD0, v8, v12);
  if ( *((_QWORD *)&v12 + 1) > 0xFu )
  {
    v9 = (void *)v11;
    if ( (unsigned __int64)(*((_QWORD *)&v12 + 1) + 1LL) >= 0x1000 )
    {
      v9 = *(void **)(v11 - 8);
      if ( (unsigned __int64)(v11 - (_QWORD)v9 - 8) > 0x1F )
      {
        sub_18006DDB8(0, 0, 0, 0, 0);
        JUMPOUT(0x180005699LL);
      }
    }
    sub_18005F438(v9);
  }
  return &qword_1800B1DD0;
}
````

它先拼接一个原始字符串，组成如下:

- `prefix`（静态字节经 XOR 解混淆）
- `pbInput`（与机器相关的输入）
- `suffix`（静态字节经 XOR 解混淆）

然后把这个原始字符串传给:

- `sub_180004300`

sub_180004300的输出才被写入 qword_1800B1DD0

````
__int64 __fastcall sub_180004300(__int64 a1, const BYTE *a2, DWORD a3)
{
  CHAR *v6; // rbx
  DWORD pcchString; // [rsp+30h] [rbp-48h] BYREF
  DWORD pdwDataLen; // [rsp+34h] [rbp-44h] BYREF
  HCRYPTHASH phHash; // [rsp+38h] [rbp-40h] BYREF
  HCRYPTPROV phProv; // [rsp+40h] [rbp-38h] BYREF
  BYTE pbData[16]; // [rsp+48h] [rbp-30h] BYREF

  pdwDataLen = 16;
  phProv = 0;
  phHash = 0;
  CryptAcquireContextA(&phProv, 0, 0, 1u, 0xF0000000);
  CryptCreateHash(phProv, 0x8003u, 0, 0, &phHash);
  CryptHashData(phHash, a2, a3, 0);
  CryptGetHashParam(phHash, 2u, pbData, &pdwDataLen, 0);
  CryptDestroyHash(phHash);
  CryptReleaseContext(phProv, 0);
  pcchString = 0;
  CryptBinaryToStringA(pbData, pdwDataLen, 0x4000000Cu, 0, &pcchString);
  v6 = (CHAR *)sub_18005F8D4(pcchString);
  CryptBinaryToStringA(pbData, pdwDataLen, 0x4000000Cu, v6, &pcchString);
  *(_OWORD *)a1 = 0;
  *(_QWORD *)(a1 + 16) = 0;
  *(_QWORD *)(a1 + 24) = 0;
  sub_180020050(a1, v6);
  sub_18005F438(v6);
  return a1;
}
````

`getLocalSecretKey` 最终值为:

```text
MD5(raw_string)
raw_string = prefix + pbInput + suffix
```

探寻pbInput，`sub_180001000` -> `sub_180004C30`

![image-20260307135414953](./img/image-20260307135414953.png)

读取注册表:

- 键: `HKLM\\SOFTWARE\\Microsoft\\Cryptography`
- 值: `MachineGuid`

还原`prefix` 与 `suffix`

根据 `sub_180005510` 中 XOR 解码循环，可还原常量:

prefix = "H22`OB~6i{A{TXqIqPEg"

suffix = "|Wea6ywQQ`1q>_QyY2f1"

所以需要得到注册表值，接第一题

![image-20260307140155566](./img/image-20260307140155566.png)

`dfa96070-797f-4b50-bb3e-d478d5c44179`

![image-20260307140313291](./img/image-20260307140313291.png)

成功得到AESkey：5a569f2670ad9d9765df113e1417083f，AESiv：UTOOLS0123456789

接下来就可以对这三个文件夹下的文件进行分析了

![image-20260307141238445](./img/image-20260307141238445.png)

先对最新的文件夹下的文件（1772701170720）进行分析

![image-20260307141718285](./img/image-20260307141718285.png)

得到一个关键信息：`第三密钥为第二密钥生成时间的时间戳`

但是没有找到第一密钥，那继续对1772700955558这个文件夹进行分析，解密后搜索key等关键词并没有找到，可能嫌疑人对文件进行了修改，

![image-20260307144123500](./img/image-20260307144123500.png)

这个的时间戳不太正常，是修改过的，可以猜测这个字符串是第一密钥，

```
zQt$d3!GIS9l.aR@7ELN
```

### 4.得到第二密钥的对话id和时间。请以 UTC+8 时区提供您的答案。（时间格式YYYY/MM/DDTHH:MM:SS，两个答案以_相连）

由"There is a key generated by AI"

接下来对cherry sudio和ollama进行分析

cherry studio的聊天记录储存在000003.log文件中

![image-20260307144732968](./img/image-20260307144732968.png)

ollama的聊天记录在sqlite数据库中,参考：

![image-20260307150310293](./img/image-20260307150310293.png)

![image-20260307151208010](./img/image-20260307151208010.png)

![image-20260307151120510](./img/image-20260307151120510.png)

```
019cbe60-6803-70fe-8ab5-e0035399980f_2026/03/05T22:25:24
```

### 5.最终可以使用的完整密钥的内容。

根据之前的”第三密钥为第二密钥生成时间的时间戳“得到第三密钥

![image-20260307154428360](./img/image-20260307154428360.png)

由“2.The key has four parts

3.Key usage requires reshuffling order: 1-4-3-2”

现在我们还缺少第四个密钥，继续看看utools的数据库

AppData\Roaming\uTools\database\

![image-20260307162310390](./img/image-20260307162310390.png)

[SuperMarcus/LevelDBViewer: 一个 Java 程序提供访问和编辑 LevelDB 数据库的功能 --- SuperMarcus/LevelDBViewer: A Java program provides ablities to access & edit leveldb database](https://github.com/SuperMarcus/LevelDBViewer)

![image-20260307162754021](./img/image-20260307162754021.png)

```
key4:A9!fK2@pL4#tM6$wN8%yR1^uD3&hJ5*Z
```

然后我们就能得到完整的可使用密钥了，根据`Key usage requires reshuffling order: 1-4-3-2`

```
zQt$d3!GIS9l.aR@7ELNA9!fK2@pL4#tM6$wN8%yR1^uD3&hJ5*Z17727207244dE23eFgH7kLmNpOqRstUvWxYz012345678901234567890123456789
```

### 6.ollama客户端no such host的时间(时间格式YYYY/MM/DDTHH:MM:SS)。

参考[技术分享 | 从痕迹到证据：大模型行为分析实战](https://mp.weixin.qq.com/s?__biz=MzI0OTU0NjA5Ng==&mid=2247518011&idx=1&sn=8b092e98c2ce85e561fb9292a4baa28a&chksm=e8f0d6f41462ef051d9ebe14190442cc4f500b7797a585fb6f080cb6be002dba19c7c95da50a&mpshare=1&scene=23&srcid=0222E6xETn8c4KLySTIKkAz1&sharer_shareinfo=06635315bd76eaf9c60c5dfd87bdb667&sharer_shareinfo_first=06635315bd76eaf9c60c5dfd87bdb667#rd)

在其app.log中

![image-20260307170555587](./img/image-20260307170555587.png)

```
2026/03/05T21:58:17
```

### 7.为了让本地模型输出固定格式的密钥，嫌疑人最后在某一会话中得到了这个prompt，请提供得到这个promot的messageid。

这个的答案在cherrystudio的日志中，AppData\Roaming\CherryStudio\IndexedDB\file__0.indexeddb.leveldb\

000003.log中

可以使用正则匹配匹配从ollama中找到的`openssl rand -base64 32 | tr '+/' '-_' | tr -d '='`

然后使用AI对这附件的上下文进行重构

![image-20260309213340746](./img/image-20260309213340746.png)



![image-20260309212856739](./img/image-20260309212856739.png)

```json
blocks": [
          {
            "id": "5a32abe3-ccfa-4007-8930-f4b30837f20a",
            "messageId": "40854344-3f6e-4464-a07f-b39d42f5adc5",
            "type": "main_text",
            "createdAt": "2026-03-05T14:20:24.225Z",
            "status": "success",
            "content_decoded": {
              "utf16le_offset": 0,
              "readability_score": 0.990826,
              "decoded_text": "潣瑮湥捴Ψ当然，可以用 **OpenSSL**（不用 Python）：\n### 生成 256 位密钥（Base64）\n```bash\nopenssl rand -base64 32\n```\n### 如果你要 URL-safe 版本（适合 JWT 场景）\n```bash\nopenssl rand -base64 32 | tr '+/' '-_' | tr -d '='\n```\n> `32` 代表 32 字节 = 256 位。"
            }
          }
        ]
      },
```

得到messageid

```
40854344-3f6e-4464-a07f-b39d42f5adc5
```

### FLAG

![image-20260309214023059](./img/image-20260315130707834.png)

flag:SUCTF{39e850db5d740c54df4281e39fb3866d}
