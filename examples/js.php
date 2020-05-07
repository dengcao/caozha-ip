<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>caozha-ip：使用演示</title>
<?php
include_once("../src/caozha_ip.class.php");
$caozha=new caozha_ip();
?>
</head>
<body>
<b>您查询的IP是：<?php $ip="202.103.222.209";echo $ip;?>，返回数据如下：</b>
<pre style="word-break:break-all;white-space:pre-wrap;">
js：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("js",$ip));
?>
</pre>
</body>
</html>