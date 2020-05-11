<?php
/*
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆
☆                                                                                              ☆
☆  源 码：caozha-ip                                                                             ☆
☆  日 期：2020-05-11                                                                            ☆
☆  开 发：草札(www.caozha.com)                                                                   ☆
☆  鸣 谢：穷店(www.qiongdian.com) 品络(www.pinluo.com)                                            ☆
☆  Git仓库: https://gitee.com/caozha/caozha-ip                                                  ☆
☆  声明：读取和处理qqwry.dat的代码，原作者是：joyphper。比较懒，直接用它了。^_^                          ☆
☆  这个类读取与处理qqwry.dat的代码是根据joyphper的“IP地理位置查询类1.0”修改，原代码版权归原作者所有。       ☆
☆                                                                                               ☆
☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆☆
*/

class caozha_ip {

	var $dat_config = "qqwry.dat";   //纯真数据库，这里注意，根据你的数据库存放位置不同，把这个数据库进行引入。
	
	
	private $fp;//IP库文件指针 resource
	private $firstip;//第一条IP记录的偏移地址 int
	private $lastip;//最后一条IP记录的偏移地址 int
	private $totalip;//IP记录的总条数（不包含版本信息记录） int
	
	
	/**
	* 构造函数，打开.Dat文件并初始化类中的信息
	*
	* @param string $filename
	* @return IpLocation
	*/
	public function __construct() {
		$filename=$this->dat_config;
		$this->fp = 0;
		if (($this->fp = fopen($filename, 'rb',1)) !== false) {
			$this->firstip = $this->getlong();
			$this->lastip = $this->getlong();
			$this->totalip = ($this->lastip - $this->firstip) / 7;//注册析构函数，使其在程序执行结束时执行
			register_shutdown_function(array(&$this, '__destruct'));
		}
	}
	
	/**
	* 析构函数，用于在页面执行结束后自动关闭打开的文件。
	*
	*/
	public function __destruct() {
		if ($this->fp) {
			fclose($this->fp);
		}
		$this->fp = 0;
	}
	
	/**
	* 返回读取的长整型数
	*
	* @access private
	* @return int
	*/
	private function getlong() {//将读取的little-endian编码的4个字节转化为长整型数
	$result = unpack('Vlong', fread($this->fp, 4));
	return $result['long'];
	}
	
	/**
	* 返回读取的3个字节的长整型数
	*
	* @access private
	* @return int
	*/
	private function getlong3() {//将读取的little-endian编码的3个字节转化为长整型数
	$result = unpack('Vlong', fread($this->fp, 3).chr(0));
	return $result['long'];
	}
	
	/**
	* 返回压缩后可进行比较的IP地址
	*
	* @access private
	* @param string $ip
	* @return string
	*/
	private function packip($ip) {// 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，// 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
	return pack('N', intval(ip2long($ip)));
	}
	
	/**
	* 返回读取的字符串
	*
	* @access private
	* @param string $data
	* @return string
	*/
	private function getstring($data = "") {
	$char = fread($this->fp, 1);
	while (ord($char) > 0) {// 字符串按照C格式保存，以\0结束
	$data .= $char;// 将读取的字符连接到给定字符串之后
	$char = fread($this->fp, 1);
	}
	return $data;
	}
	
	/**
	* 返回地区信息
	*
	* @access private
	* @return string
	*/
	private function getarea() {
		$byte = fread($this->fp, 1);// 标志字节
		switch (ord($byte)) {
		case 0:// 没有区域信息
			$area = "";
			break;
		case 1:
		case 2:// 标志字节为1或2，表示区域信息被重定向
			fseek($this->fp, $this->getlong3());
			$area = $this->getstring();
			break;
		default:// 否则，表示区域信息没有被重定向
			$area = $this->getstring($byte);
			break;
		}
		return $area;
	}
	
	/**
	* 根据所给 IP 地址或域名返回所在地区信息
	*
	* @access public
	* @param string $ip
	* @return array
	*/
	public function get($ip) {
		if (!$this->fp) return null;// 如果数据文件没有被正确打开，则直接返回空
		$location['ip'] = gethostbyname($ip);   // 将输入的域名转化为IP地址
		$ip = $this->packip($location['ip']);   // 将输入的IP地址转化为可比较的IP地址
		// 不合法的IP地址会被转化为255.255.255.255// 对分搜索
		$l = 0;// 搜索的下边界
		$u = $this->totalip;// 搜索的上边界
		$findip = $this->lastip;// 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息）
		while ($l <= $u) {// 当上边界小于下边界时，查找失败
			$i = floor(($l + $u) / 2); // 计算近似中间记录
			fseek($this->fp, $this->firstip + $i * 7);
			$beginip = strrev(fread($this->fp, 4));// 获取中间记录的开始IP地址// strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式// 以便用于比较，后面相同。
			if ($ip < $beginip) {// 用户的IP小于中间记录的开始IP地址时
				$u = $i - 1;// 将搜索的上边界修改为中间记录减一
			}else{
				fseek($this->fp, $this->getlong3());
				$endip = strrev(fread($this->fp, 4));   // 获取中间记录的结束IP地址
				if ($ip > $endip) {// 用户的IP大于中间记录的结束IP地址时
					$l = $i + 1;// 将搜索的下边界修改为中间记录加一
				}else{// 用户的IP在中间记录的IP范围内时
					$findip = $this->firstip + $i * 7;
					break;// 则表示找到结果，退出循环
				}
			}
		}//获取查找到的IP地理位置信息
		fseek($this->fp, $findip);
		$location['beginip'] = long2ip($this->getlong());   // 用户IP所在范围的开始地址
		$offset = $this->getlong3();
		fseek($this->fp, $offset);
		$location['endip'] = long2ip($this->getlong());// 用户IP所在范围的结束地址
		$byte = fread($this->fp, 1);// 标志字节
		switch (ord($byte)) {
		case 1:// 标志字节为1，表示国家和区域信息都被同时重定向
			$countryOffset = $this->getlong3();// 重定向地址
			fseek($this->fp, $countryOffset);
			$byte = fread($this->fp, 1);// 标志字节
			switch (ord($byte)) {
			case 2:// 标志字节为2，表示国家信息又被重定向
				fseek($this->fp, $this->getlong3());
				$location['country'] = $this->getstring();
				fseek($this->fp, $countryOffset + 4);
				$location['area'] = $this->getarea();
				break;
			default:// 否则，表示国家信息没有被重定向
				$location['country'] = $this->getstring($byte);
				$location['area'] = $this->getarea();
				break;
			}
			break;
		case 2:// 标志字节为2，表示国家信息被重定向
			fseek($this->fp, $this->getlong3());
			$location['country'] = $this->getstring();
			fseek($this->fp, $offset + 8);
			$location['area'] = $this->getarea();
			break;
		default:// 否则，表示国家信息没有被重定向
			$location['country'] = $this->getstring($byte);
			$location['area'] = $this->getarea();
			break;
		}
		if ($location['country'] == " CZ88.NET") { // CZ88.NET表示没有有效信息
			$location['country'] = "未知";
		}
		if ($location['area'] == " CZ88.NET") {
			$location['area'] = "";
		}
		$location['country']=iconv('gbk', 'utf-8', $location['country']);
		$location['area']=iconv('gbk', 'utf-8', $location['area']);
		return $location;
	}  
	
	
	/*
	方法：IP转地址
	参数：
	$ip 要查询的IP地址，为空时自动获取本机IP
	$format 输出格式，可选值：js、json、jsonp、text、xml
	$callback 回调函数名，当$format=jsonp时设置
	*/
	function ip_to_address( $format = "json", $ip = "", $callback = "" ) {
		if ( !$ip ) {
			$ip = $this->getip();
		}
		
		$IP_addr=$this->get($ip);		
		$IP_addr_new = $this->splitAddress( $IP_addr[ "country" ] );
		
		
		$list_arr = array(
			"ip" => $ip,
			"ip_start" => $IP_addr[ "beginip" ],
			"ip_end" => $IP_addr[ "endip" ],
			"address" => $IP_addr[ "country" ],
			"location" => $IP_addr[ "area" ],
			"country" => $IP_addr_new[ "country" ],
			"province" => $IP_addr_new[ "province" ],
			"city" => $IP_addr_new[ "city" ],
			"area" => $IP_addr_new[ "area" ]
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
			case "text":
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


}
