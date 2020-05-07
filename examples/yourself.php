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
	<b>您的IP是：<?=$caozha->getip()?>，详细信息如下：</b>
<pre style="word-break:break-all;white-space:pre-wrap;">
XML：<br>
<?php
	echo htmlspecialchars($caozha->ip_to_address("xml"));
?>
</pre>
</body>
</html>