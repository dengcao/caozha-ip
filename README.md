# caozha-ip（PHP）

caozha-ip，是基于原生PHP写的一套完整的IP转地址模块，支持自动获取IP，也支持查询指定IP，同时支持输出json、jsonp、text、xml、js等多种IP和地址格式，还可以细分为国家、省、市、地区，方便在各种系统里整合与调用。数据库采用MYSQL，IP地址数据来自纯真IP数据库等。

### 使用方法

MYSQL版本：

1、将src/caozha_ip.sql.zip导入到MYSQL数据库。

2、配置好src/caozha_ip.class.php数据库信息。

3、参考实例：examples/ （内含多种调用方式）


NoSQL版本（无需数据库）：

1、引入src-NoSQL/caozha_ip.class.php

2、参考实例：examples/NoSQL.php


### IP数据更新方法

1、网上下载最新版的纯真IP数据库，安装后打开软件，点击“解压”，得到文件：qqwry.txt，放在目录convert/里。

2、修改convert/convert.php里的数据库配置，并运行此PHP程序，执行对应的操作，即可完成IP数据的更新。

### 赞助支持：

支持本程序，请到Gitee和GitHub给我们点Star！

Gitee：https://gitee.com/caozha/caozha-ip

GitHub：https://github.com/cao-zha/caozha-ip

### 关于开发者

开发：草札 www.caozha.com

鸣谢：品络 www.pinluo.com  &ensp;  穷店 www.qiongdian.com

### 体验地址

IP地址归属地查询  https://diannao.wang/tool/ip/

### 接口预览

![输入图片说明](https://images.gitee.com/uploads/images/2020/0508/104100_70342dc1_7397417.png "1")
![输入图片说明](https://images.gitee.com/uploads/images/2020/0508/104113_eac0fbba_7397417.png "2")
![输入图片说明](https://images.gitee.com/uploads/images/2020/0508/104123_e8e90e7d_7397417.png "3")



