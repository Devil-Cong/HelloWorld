<?php

// getChannelList();

// $list = queryChannelPage();

$list = querySound();

// ParallelRequestWithCookie($list);

// $list     = queryChannel();

$tempList    = [[], [], [], [], [], [], [], [], [], [], []];
$tempList[1] = [];
foreach ($list as $key => $value) {
	$remainder = $key % 10;
	switch ($remainder) {
	case 0:
		array_push($tempList[1], $value);
		break;
	case 1:
		array_push($tempList[2], $value);
		break;
	case 2:
		array_push($tempList[3], $value);
		break;
	case 3:
		array_push($tempList[4], $value);
		break;
	case 4:
		array_push($tempList[5], $value);
		break;
	case 5:
		array_push($tempList[6], $value);
		break;
	case 6:
		array_push($tempList[7], $value);
		break;
	case 7:
		array_push($tempList[8], $value);
		break;
	case 8:
		array_push($tempList[9], $value);
		break;
	case 9:
		array_push($tempList[10], $value);
		break;
	}
}

$start = time();
echo "\nSCRIT RUN AT: ", date('Y-m-d H:i:s', $start), "\n";

$pmax = 10;
//父进程pid
$ppid = getmypid();
for ($i = 1; $i <= $pmax; ++$i) {
	//开始产生子进程
	$pid = pcntl_fork();
	switch ($pid) {
	case -1:
		// fork失败
		echo "Fork failed!\n";
		break;
	case 0:
		// fork成功，并且子进程会进入到这里
		sleep(1);
		$cpid = getmypid(); //用getmypid()函数获取当前进程的PID
		echo "FORK: Child #{$i} #{$cpid} is running...\n";
		//getChannelPageUesPcntl($tempList[$i]);
		ParallelRequestWithCookie($tempList[$i], $i);
		//子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
		exit($i);
		break;
	default:
		// fork成功，并且父进程会进入到这里
		if ($i == 1) {
			echo "Parent #{$ppid} is running...\n";
		}
		break;
	}
}

//父进程利用while循环，并且通过pcntl_waitpid函数来等待所有子进程完成后才继续向下进行
while (pcntl_waitpid(0, $status) != -1) {
	//pcntl_wexitstatus返回一个中断的子进程的返回代码，由此可判断是哪一个子进程完成了
	$status = pcntl_wexitstatus($status);
	echo "Child $status has completed!\n";
}

$end = time();
echo "\nSCRIT END AT: ", date('Y-m-d H:i:s', $end), "\n";
echo "TOTAL TIMEEEE: " . (time() - $start) / 60;

function getSoundSourceList($result, $value, $t) {
	$rule = '/var page_sound_obj \=(.+)\;/';
	preg_match($rule, $result, $preg);
	$obj = json_decode($preg[1], true);
	//获取.m3u8文件
	$result = file_get_contents($obj['source']);

	$rule = '/http\:\/\/(.+)\.ts/';
	preg_match_all($rule, $result, $preg);
	$sourceList = $preg[0];
	$mp3        = null;
	foreach ($sourceList as $key => $val) {
		$temp             = null;
		$temp['sound_id'] = $value['id'];
		$temp['url']      = $val;
		insertSoundSource($temp, $t);
		// $mp3 .= file_get_contents($val);
	}
	// $fp = @fopen(mb_convert_encoding($obj['name'], "gb2312", "utf-8") . ".mp3", 'a');
	// $fp = @fopen($obj['name'] . ".mp3", 'a');
	// fwrite($fp, $mp3);
	// fclose($fp);
	// echo $obj['name'].' finish'."\n";
}

function getChannelPageUesPcntl($list) {
	foreach ($list as $key => $value) {
		getChannelPage($value);
	}
}

function getChannelList() {
	for ($j = 1; $j <= 12; $j++) {
		$url    = 'http://www.app-echo.com/channel/list?&page=' . $j . '&per-page=16';
		$result = API::RequestWithCookie($url);
		$rule   = '/<h3>(.+)<\/h3>|<a href\=\"(.+)\"><\/a>/';
		preg_match_all($rule, $result, $preg);

		$rule = '/\/channel\/\d+/';
		$list = [];
		for ($i = 0; $i < 32; $i++) {
			if ($i % 2) {
				$temp['url'] = $preg[2][$i];
				if (preg_match($rule, $temp['url'])) {
					insertChannel($temp);
					// array_push($list, $temp);
				}
				$temp = null;
			} else {
				$temp['title'] = $preg[1][$i];
			}
		}
	}
}

function getChannelPage($value) {
	for ($j = 1; $j < 100; $j++) {
		$url    = 'http://www.app-echo.com' . $value['url'] . '?&page=' . $j . '&per-page=14';
		$result = API::RequestWithCookie($url);
		$rule   = '/<h3 class\=\"voice-name\"><a href\=\"(.+)\">(.+)<\/a><\/h3>|title\=\"分享数目\">(.+)<\/small>|title\=\"喜欢数目\">(.+)<\/small>|title\=\"评论数目\">(.+)<\/small>/';
		preg_match_all($rule, $result, $preg);
		if ($preg[0]) {
			$temp['fid'] = $value['id'];
			$temp['url'] = $url;
			insertChannelPage($temp);
		} else {
			break;
		}
	}
}

function insertSoundSource($temp, $t) {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql = "INSERT INTO `em_sound_source` (`sound_id`, `url`) VALUES ('" . $temp['sound_id'] . "', '" . $temp['url'] . "');";
	mysql_query($sql, $db_connect);
	echo 'FORK: Child #' . $t . ':' . $sql . "\n";
}

function insertSound($temp) {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql = "INSERT INTO `em_sound` (`channel_id`, `title`, `url`, `share`, `like`, `comment`) VALUES ('" . $temp['channel_id'] . "', '" . $temp['title'] . "', '" . $temp['url'] . "', '" . $temp['share'] . "', '" . $temp['like'] . "', '" . $temp['comment'] . "');";
	mysql_query($sql, $db_connect);
	echo $sql . "\n";
}

function insertChannelPage($temp) {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql = "INSERT INTO `em_channel_page` (`fid`, `url`) VALUES ('" . $temp['fid'] . "', '" . $temp['url'] . "');";
	mysql_query($sql, $db_connect);
	echo $sql . "\n";
}

function insertChannel($temp) {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql = "INSERT INTO `em_channel` (`title`, `url`) VALUES ('" . $temp['title'] . "', '" . $temp['url'] . "');";
	mysql_query($sql, $db_connect);
	echo $sql . "\n";
}

function queryChannel() {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql    = "SELECT * FROM `em_channel`;";
	$result = mysql_query($sql, $db_connect);
	while ($row[] = mysql_fetch_array($result));
	return $row;
}

function queryChannelPage() {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql    = "SELECT * FROM `em_channel_page`;";
	$result = mysql_query($sql, $db_connect);
	while ($row[] = mysql_fetch_array($result));
	return $row;
}

function querySound() {
	//连接数据库
	$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
	mysql_select_db('echomusic', $db_connect);
	mysql_query('set names utf8;');
	$sql    = "SELECT * FROM `em_sound`;";
	$result = mysql_query($sql, $db_connect);
	while ($row[] = mysql_fetch_array($result));
	return $row;
}

function getSound($result, $value) {
	$rule = '/<h3 class\=\"voice-name\"><a href\=\"(.+)\">(.+)<\/a><\/h3>|title\=\"分享数目\">(.+)<\/small>|title\=\"喜欢数目\">(.+)<\/small>|title\=\"评论数目\">(.+)<\/small>/';
	preg_match_all($rule, $result, $preg);
	if ($preg[0]) {
		$list = [];
		for ($i = 0; $i < 56; $i++) {
			$remainder = $i % 4;
			switch ($remainder) {
			case 0:
				$temp['channel_id'] = $value['id'];
				$temp['title']      = $preg[2][$i];
				$temp['url']        = $preg[1][$i];
				break;
			case 1:
				$temp['share'] = $preg[3][$i];
				break;
			case 2:
				$temp['like'] = $preg[4][$i];
				break;
			case 3:
				$temp['comment'] = $preg[5][$i];
				if ($temp['title']) {
					insertSound($temp); // array_push($list, $temp);
				}
				$temp = null;
				break;
			}
		}
	} else {
		break;
	}
}

function ParallelRequestWithCookie($urls, $t) {
	$cookie   = '_csrf=9da154dde6607b1eb566c786ff2d6290b21b30b8fb00d33c478166efe2e339c4a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22pyYUQDN76yJ89mm5pLfOZucLPmAGn4Os%22%3B%7D; PHPSESSID=ld98k58rums89cujidkkcqglm3; Hm_lvt_46b3b8e7eb78200527b089c276c81a7e=1453967669; Hm_lpvt_46b3b8e7eb78200527b089c276c81a7e=1453967693';
	$max_size = 20;
	$mh       = curl_multi_init();
	for ($i = 0; $i < $max_size; $i++) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.app-echo.com' . $urls[$i]['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		curl_multi_add_handle($mh, $ch);
	}
	$user_arr = array();

	do {
		//运行当前 cURL 句柄的子连接
		while (($cme = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM);

		if ($cme != CURLM_OK) {break;}
		//获取当前解析的cURL的相关传输信息
		while ($done = curl_multi_info_read($mh)) {
			$info       = curl_getinfo($done['handle']);
			$tmp_result = curl_multi_getcontent($done['handle']);
			$error      = curl_error($done['handle']);

			// getSound($tmp_result, $urls[$i]);
			getSoundSourceList($tmp_result, $urls[$i], $t);

			//保证同时有$max_size个请求在处理
			if ($i < sizeof($urls) && isset($urls[$i]) && $i < count($urls)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'http://www.app-echo.com' . $urls[$i]['url']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
				curl_multi_add_handle($mh, $ch);
				$i++;
			}

			curl_multi_remove_handle($mh, $done['handle']);
		}

		if ($active) {
			curl_multi_select($mh, 10);
		}

	} while ($active);

	curl_multi_close($mh);
	// return $user_arr;
}

class API {
	static function RequestWithCookie($url) {
		$cookie = '_csrf=9da154dde6607b1eb566c786ff2d6290b21b30b8fb00d33c478166efe2e339c4a%3A2%3A%7Bi%3A0%3Bs%3A5%3A%22_csrf%22%3Bi%3A1%3Bs%3A32%3A%22pyYUQDN76yJ89mm5pLfOZucLPmAGn4Os%22%3B%7D; PHPSESSID=ld98k58rums89cujidkkcqglm3; Hm_lvt_46b3b8e7eb78200527b089c276c81a7e=1453967669; Hm_lpvt_46b3b8e7eb78200527b089c276c81a7e=1453967693';
		$ch     = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}
