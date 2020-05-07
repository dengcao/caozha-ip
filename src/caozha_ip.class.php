<?php
/*
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆
☆                                                                         ☆
☆  源 码：caozha-ip                                                        ☆
☆  日 期：2020-05-06                                                       ☆
☆  开 发：草札(www.caozha.com)                                              ☆
☆  鸣 谢：穷店(www.qiongdian.com) 品络(www.pinluo.com)                       ☆
☆  Git仓库: https://gitee.com/caozha/caozha-ip                             ☆
☆  Copyright ©2020 www.caozha.com All Rights Reserved.                    ☆
☆                                                                         ☆
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆
*/

class caozha_ip {
	/*
		MYSQL数据库配置，请修改。		
		下面参数依次为：数据库服务器，数据库用户名，数据库密码，IP数据库名称，数据库字符集，IP数据表
	*/
	var $mysql_config = array(
		"mysql_server" => "localhost",
		"mysql_username" => "root",
		"mysql_password" => "root",
		"mysql_database_name" => "caozha_ip",
		"mysql_charset" => "utf8",
		"mysql_database_table" => "caozha_ip_data"
	);


	/*
	方法：IP转地址
	参数：
	$ip 要查询的IP地址，为空时自动获取本机IP
	$format 输出格式，可选值：js、json、jsonp、txt、xml
	$callback 回调函数名，当$format=jsonp时设置
	*/
	function ip_to_address( $format = "json", $ip = "", $callback = "" ) {
		if ( !$ip ) {
			$ip = $this->getip();
		}
		$config = $this->mysql_config;
		$conn = mysqli_connect( $config[ "mysql_server" ], $config[ "mysql_username" ], $config[ "mysql_password" ] )or die( "数据库连接错误，请配置正确。" );
		mysqli_select_db( $conn, $config[ "mysql_database_name" ] );
		mysqli_set_charset( $conn, $config[ "mysql_charset" ] );
		$sql = mysqli_query( $conn, "select * from `" . $config[ "mysql_database_table" ] . "` where INET_ATON('" . addslashes( strip_tags( $ip ) ) . "') between INET_ATON(ip_start) and INET_ATON(ip_end) limit 0,1" );
		while ( $list_r = mysqli_fetch_array( $sql ) ) {
			$list = $list_r;
		}
		mysqli_close( $conn );
		$list_arr = array(
			"ip" => $ip,
			"ip_start" => $list[ "ip_start" ],
			"ip_end" => $list[ "ip_end" ],
			"address" => $list[ "address" ],
			"location" => $list[ "location" ],
			"country" => $list[ "country" ],
			"province" => $list[ "province" ],
			"city" => $list[ "city" ],
			"area" => $list[ "area" ]
		);
		return $this->out_format( $list_arr, $format, $callback );

	}

	function out_format( $list, $format, $callback ) { //按格式输出数据
		switch ( $format ) {
			case "js":
				return "var ip_info = " . json_encode( $list ) . ";";
				break;
			case "json":
				return json_encode( $list );
				break;
			case "jsonp":
				return $callback . "(" . json_encode( $list ) . ");";
				break;
			case "txt":
				return implode( "|", $list );
				//return "ip:" . $list[ "ip" ] . ",ip_start:" . $list[ "ip_start" ] . ",ip_end:" . $list[ "ip_end" ] . ",address:" . $list[ "address" ] . ",location:" . $list[ "location" ] . ",country:" . $list[ "country" ] . ",province:" . $list[ "province" ] . ",city:" . $list[ "city" ] . ",area:" . $list[ "area" ];
				break;
			case "xml":
				return $this->arrayToXml( $list );
				break;
			default:
				return json_encode( $list );
		}
	}


	function arrayToXml( $arr ) { //数组转XML
		$xml = "<root>";
		foreach ( $arr as $key => $val ) {
			if ( is_array( $val ) ) {
				$xml .= "<" . $key . ">" . $this->arrayToXml( $val ) . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			}
		}
		$xml .= "</root>";
		return $xml;
	}

	function getip() { //获取客户端IP

		if ( $_SERVER[ "HTTP_CDN_SRC_IP" ] ) { //获取网宿CDN真实客户IP

			return $this->replace_ip( $_SERVER[ "HTTP_CDN_SRC_IP" ] );

		}

		if ( $_SERVER[ "HTTP_X_FORWARDED_FOR" ] ) { //获取网宿、阿里云真实客户IP，参考：https://help.aliyun.com/knowledge_detail/40535.html

			return $this->replace_ip( $_SERVER[ "HTTP_X_FORWARDED_FOR" ] );

		}

		if ( $_SERVER[ "HTTP_CLIENT_IP" ] ) {

			return $_SERVER[ "HTTP_CLIENT_IP" ];

		}

		if ( $_SERVER[ "HTTP_X_FORWARDED" ] ) {

			return $_SERVER[ "HTTP_X_FORWARDED" ];

		}

		if ( $_SERVER[ "HTTP_FORWARDED_FOR" ] ) {

			return $_SERVER[ "HTTP_FORWARDED_FOR" ];

		}

		if ( $_SERVER[ "HTTP_FORWARDED" ] ) {

			return $_SERVER[ "HTTP_FORWARDED" ];

		}

		$httpip = $_SERVER[ 'REMOTE_ADDR' ];

		if ( !preg_match( "/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/", $httpip ) ) {

			$httpip = "127.0.0.1";

		}

		return $httpip;

	}

	function replace_ip( $ip ) {

		if ( !$ip ) {
			return "";
		}

		$httpip_array = explode( ",", $ip );

		if ( $httpip_array[ 0 ] ) {

			return $httpip_array[ 0 ];

		} else {

			return $ip;

		}

	}


}