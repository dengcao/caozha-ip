<?php
/*
因为处理数据庞大，较消耗服务器资源，所以Apache或Nginx必须设置足够长的超时时间，否则会错误。
*/
ignore_user_abort(); //即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行
set_time_limit( 0 ); //不限制超时时间
ini_set( 'memory_limit', '6400M' );
/*
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆
☆                                                                         ☆
☆  源 码：纯真IP库转换MYSQL工具                                              ☆
☆  日 期：2020-05-06                                                       ☆
☆  开 发：草札(www.caozha.com)                                              ☆
☆  鸣 谢：穷店(www.qiongdian.com) 品络(www.pinluo.com)                       ☆
☆  Git仓库: https://gitee.com/caozha/caozha-ip                             ☆
☆  Copyright ©2020 www.caozha.com All Rights Reserved.                    ☆
☆                                                                         ☆
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆

以下为配置选项（可根据实际修改）：

*/

$file_path = "qqwry.txt"; //txt格式的纯真IP数据库文件
$mysql_server = "localhost"; //数据库服务器
$mysql_username = "root"; //数据库用户名
$mysql_password = "root"; //数据库密码
$mysql_database_name = "caozha_ip"; //IP数据库名称
$mysql_charset = "utf8"; //数据库字符集
$mysql_database_table = "caozha_ip_data"; //IP数据表


/*
----------------以下代码请勿更改----------------
*/

$action = $_GET[ "action" ];
if ( $action == "import" ) {
	@import_mysql();
} elseif ( $action == "convert" ) {
	@convert_ip();
} else {
	$url = "http://" . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ];
	$html = "<b>纯真IP库转换工具：</b><br><br>1、将txt格式的纯真IP数据库导入到MYSQL：" . $url . "?action=import（<font color=red>会清空原数据表</font>）";
	$html .= "<br>2、将IP数据库内的地址细分为省市区：" . $url . "?action=convert";
	echo_html( $html );
}


function import_mysql() { //将txt格式的纯真IP数据库导入到MYSQL

	global $file_path, $mysql_server, $mysql_username, $mysql_password, $mysql_database_name, $mysql_charset, $mysql_database_table;

	$conn = mysqli_connect( $mysql_server, $mysql_username, $mysql_password )or die( "数据库连接错误，请编辑convert.php文件配置正确。" );
	mysqli_select_db( $conn, $mysql_database_name );
	mysqli_set_charset( $conn, $mysql_charset );

	if ( file_exists( $file_path ) ) {

		$query = mysqli_query( $conn, "TRUNCATE `" . $mysql_database_table . "`" ); //清空数据表
		$i = 0;

		$fp = fopen( $file_path, "r" );

		while ( !feof( $fp ) ) {

			$str = fgets( $fp ); //逐行读取				
			$str = @iconv( "gb2312", "utf-8//IGNORE", $str ); //转码处理，防止乱码
			/*$str = str_ireplace( "  ", " ", str_ireplace( "  ", " ", $str ) );
			$str_arr = explode( " ", $str );*/
			@preg_match_all( "/^(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})(\s+)(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})(\s+)([\s\S]*?)(\s+)([\s\S]*?)$/i", $str, $str_arr, PREG_SET_ORDER );
			$str_arr = $str_arr[ 0 ];
			if ( $str_arr[ 1 ] ) {
				$str_arr[ 7 ] = str_ireplace( "CZ88", "", str_ireplace( "CZ88.NET", "", $str_arr[ 7 ] ) );
				$query = @mysqli_query( $conn, "INSERT INTO `" . $mysql_database_table . "` (`ip_start`, `ip_end`, `address`, `location`) VALUES ('" . $str_arr[ 1 ] . "', '" . $str_arr[ 3 ] . "', '" . $str_arr[ 5 ] . "', '" . $str_arr[ 7 ] . "');" );
				$i += 1;
			}
			unset( $str_arr );

		}

		echo_html( "导入完成。共成功导入" . $i . "条IP数据。" );

	} else {
		echo_html( "纯真IP数据库TXT文件" . $file_path . "不存在，请编辑convert.php文件配置正确。" );
	}

	mysqli_close( $conn );
	exit;

}


function convert_ip() { //将IP数据库内的地址细分为省市区

	$num_config = 20000; //每次处理个数

	$start_id = 1;

	if ( $_GET[ "start_id" ] > 1 ) {
		$start_id = $_GET[ "start_id" ];
	}

	$next_id = $start_id + $num_config; //下一次开始ID
	$next_end_id = $next_id + $num_config; //下一次结束ID

	global $file_path, $mysql_server, $mysql_username, $mysql_password, $mysql_database_name, $mysql_charset, $mysql_database_table;
	$i = 0;

	$conn = mysqli_connect( $mysql_server, $mysql_username, $mysql_password )or die( "数据库连接错误，请编辑convert.php文件配置正确。" );
	mysqli_select_db( $conn, $mysql_database_name );
	mysqli_set_charset( $conn, $mysql_charset );
	$sql = mysqli_query( $conn, "select `ip_start`,`ip_end`,`address` from `" . $mysql_database_table . "` limit " . ( $start_id - 1 ) . "," . $num_config );
	$result_num = mysqli_num_rows( $sql );
	while ( $list = mysqli_fetch_array( $sql ) ) {
		$addr = splitAddress( $list[ "address" ] );
		if ( $addr[ "country" ] ) {
			$query_result = @mysqli_query( $conn, "update `" . $mysql_database_table . "` set `country`='" . addslashes( $addr[ "country" ] ) . "',`province`='" . addslashes( $addr[ "province" ] ) . "',`city`='" . addslashes( $addr[ "city" ] ) . "',`area`='" . addslashes( $addr[ "area" ] ) . "' where `ip_start`='" . $list[ "ip_start" ] . "' and `ip_end`='" . $list[ "ip_end" ] . "'" );
			if ( $query_result ) {
				$i += 1;
			}
		}
	}
	if ( $result_num == $num_config ) {
		echo_html( "<b>将IP数据库内的地址细分为省市区：</b><br><br>" );
		echo_html( "本批次（行：" . $start_id . " - " . ( $next_id - 1 ) . "）已处理完成。共需处理" . $num_config . "条，成功转换" . $i . "条。<br><br>系统将自动处理下一批IP数据（行：" . $next_id . " - " . $next_end_id . "），请不要刷新页面……" );
		echo_html( "<script>location.href='?action=convert&start_id=" . $next_id . "';</script>" );
	} else {
		echo_html( "已全部完成转换。" );
	}

	mysqli_close( $conn );
	exit;
}

function splitAddress( $address ) { //从IP库的地址中提取省市区等数据
	preg_match( '/(.*?(省|市|西藏|内蒙古|新疆|广西|宁夏|香港|澳门))/', $address, $matches );
	if ( count( $matches ) > 1 ) {
		$province = $matches[ count( $matches ) - 2 ];
		$address = str_replace( $province, '', $address );
	}
	preg_match( '/(.*?(市|自治州|地区|区划|县))/', $address, $matches );
	if ( count( $matches ) > 1 ) {
		$city = $matches[ count( $matches ) - 2 ];
		$address = str_replace( $city, '', $address );
	}
	preg_match( '/(.*?(市|区|县|镇|乡|街道))/', $address, $matches );
	if ( count( $matches ) > 1 ) {
		$area = $matches[ count( $matches ) - 2 ];
		$address = str_replace( $area, '', $address );
	}
	if ( $province ) {
		$country = "中国";
	} else {
		preg_match( '/^(.*?IANA.*?)$/', $address, $matches );
		if ( count( $matches ) <= 1 ) {
			$country = $address;
		}
	}
	unset( $matches );
	return array( 'country' => $country,
		'province' => isset( $province ) ? $province : '',
		'city' => isset( $city ) ? $city : '',
		'area' => isset( $area ) ? $area : '' );
}

function echo_html( $str ) {
	echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>纯真IP库转换MYSQL工具 - caozha.com</title>
</head>

<body>' . $str . '
</body>
</html>';
}