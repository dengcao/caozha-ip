<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>caozha-ip：使用演示（不需要MYSQL）</title>
<?php
include_once("../src-NoSQL/caozha_ip.class.php");
$caozha=new caozha_ip();
?>
</head>
<body>
<b>您查询的IP是：<?php $ip="202.103.222.209";echo $ip;?>，返回数据如下：</b>
<pre style="word-break:break-all;white-space:pre-wrap;">
json：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("json",$ip));
?>
<br><br>
text：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("text",$ip));
?>
<br><br>
xml：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("xml",$ip));
?>
<br><br>
jsonp：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("jsonp",$ip));
?>
<br><br>
js：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("js",$ip));
?>
</pre>
</body>
</html>
