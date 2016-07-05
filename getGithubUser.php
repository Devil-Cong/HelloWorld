<?php

// $url = 'https://github.com/fengchunsgit';
// $url = 'https://github.com/liu21st/followers';

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
// $result = curl_exec($ch);
// curl_close($ch);
// echo $result;

// // $rule = '/<h3 class\=\"follow-list-name\"><span class\=\"css-truncate css-truncate-target\" title\=\".+\"><a href\=\"(.+)\">.+<\/a><\/span><\/h3>/';

// var_dump(matchStreak($result));
// exit();
$start = date('Y-m-d H:i:s');
echo "sctipt start at: " . $start . "\n";

$GLOBALS[1]  = ['/bcoe', '/Zer0Root', '/shaunstanislaus'];
$GLOBALS[2]  = ['/chriseppstein', '/trietptm', '/vickyi'];
$GLOBALS[3]  = ['/jamesfoster', '/sebastian9', '/ismailfarooq'];
$GLOBALS[4]  = ['/liu21st', '/jl431', '/agsdot'];
$GLOBALS[5]  = ['/lukeapage', '/frankxin', '/patrickording'];
$GLOBALS[6]  = ['/ColinEberhardt', '/YangHongchen', '/anil826'];
$GLOBALS[7]  = ['/WolframHempel', '/simonewebdesign'];
$GLOBALS[8]  = ['/jamesfoster', '/martinbutler', '/amrme'];
$GLOBALS[9]  = ['/chrisprice', '/joejollands', '/hipikat'];
$GLOBALS[10] = ['/MarkRhodes', '/tokyoincode', '/dako3256'];
$GLOBALS[11] = ['/mini-thor', '/PeiJungYu', '/rahulsingh2k10'];
$GLOBALS[12] = ['/elopezanaya', '/cookiy', '/mackiecarr'];
$GLOBALS[13] = ['/tbournewd', '/hawaiianchimp', '/etdev'];
$GLOBALS[14] = ['/slowreadr', '/thiagosf', '/ourai'];
$GLOBALS[15] = ['/macressler', '/dmahipus', '/wileynet'];
$GLOBALS[16] = ['/hellosylvee', '/gabrielpconceicao'];
$GLOBALS[17] = ['/thinktopdown', '/dennisgaebel', '/dlouise64'];
$GLOBALS[18] = ['/Arcrammer', '/anyboo', '/morriswchris'];
$GLOBALS[19] = ['/pierreburel', '/tiffanytse', '/ipelekhan'];
$GLOBALS[20] = ['/jlukic', '/dustindowell22', '/boton'];

$_SESSION['count'] = 0;

$pmax = 20;
//父进程pid
$ppid = getmypid();
for ($k = 1; $k <= $pmax; ++$k) {
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
		echo "FORK: Child #{$k} #{$cpid} is running...\n";
		// parallelRequest($k);
		Request($k);
		//子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
		exit($k);
		break;
	default:
		// fork成功，并且父进程会进入到这里
		if ($k == 1) {
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

$end = date('Y-m-d H:i:s');
echo "sctipt end at: " . $end . "\n";
echo "total time: " . abs(strtotime($end) - strtotime($start)) . " seconds\n";

function Request($kk) {
	while (0 < sizeof($GLOBALS[$kk])) {
		$temp = array_pop($GLOBALS[$kk]);
		$ch   = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://github.com' . $temp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		$result = curl_exec($ch);
		curl_close($ch);
		$userInfo = handleUserInfo($result);
		insertUserInfo($userInfo, $kk);
		getUserList($userInfo, $kk);
	}
}

function parallelRequest($kk) {
	$max_size = 2; //10个并发请求
	$mh       = curl_multi_init();
	for ($i = 0; $i < $max_size; $i++) {
		if (0 < sizeof($GLOBALS[$kk])) {
			$temp = array_pop($GLOBALS[$kk]);
			$ch   = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://github.com' . $temp);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); //使用了SOCKS5代理
			curl_setopt($ch, CURLOPT_PROXY, "192.168.17.1:2016");
			curl_multi_add_handle($mh, $ch);
		}
	}
	do {
		//运行当前 cURL 句柄的子连接
		while (($cme = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM);

		if ($cme != CURLM_OK) {break;}
		//获取当前解析的cURL的相关传输信息
		while ($done = curl_multi_info_read($mh)) {
			$info       = curl_getinfo($done['handle']);
			$tmp_result = curl_multi_getcontent($done['handle']);
			$error      = curl_error($done['handle']);
			$userInfo   = handleUserInfo($tmp_result);
			insertUserInfo($userInfo);
			getUserList($userInfo, $kk);

			//保证同时有$max_size个请求在处理
			if (0 < sizeof($GLOBALS[$kk])) {
				$temp = array_pop($GLOBALS[$kk]);
				$ch   = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://github.com' . $temp);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); //使用了SOCKS5代理
				curl_setopt($ch, CURLOPT_PROXY, "192.168.17.1:2016");
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
}

function getUserList($temp, $k) {
	$url = 'https://github.com/' . $temp['user_name'] . '/followers';
	pushUserList($url, $k);
	$url = 'https://github.com/' . $temp['user_name'] . '/following';
	pushUserList($url, $k);
}

function pushUserList($url, $k) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	$result = curl_exec($ch);
	curl_close($ch);
	$rule = '/<h3 class\=\"follow-list-name\"><span class\=\"css-truncate css-truncate-target\" title\=\".+\"><a href\=\"(.+)\">.+<\/a><\/span><\/h3>/';
	preg_match_all($rule, $result, $preg);
	foreach ($preg[1] as $key => $value) {
		if (!isExists($value)) {
			array_push($GLOBALS[$k], $value);
		}
	}
	$url = matchNextPage($result);
	preg_match_all($rule, $result, $preg);
	if ($url != '') {
		pushUserList($url, $k);
	}
}

function handleUserInfo($result) {
	$temp = array(
		'full_name'      => matchFullName($result),
		'user_name'      => matchUserName($result),
		'works_for'      => matchWorksFor($result),
		'home_location'  => matchHomeLocation($result),
		'email'          => matchEmail($result),
		'url'            => matchUrl($result),
		'join_date'      => matchJoinDate($result),
		'followers'      => matchFSF($result)[0],
		'starred'        => matchFSF($result)[1],
		'following'      => matchFSF($result)[2],
		'contributions'  => matchContributions($result),
		'longest_streak' => matchStreak($result)[0],
		'current_streak' => matchStreak($result)[1],
	);
	return $temp;
}

function insertUserInfo($temp, $k) {
	if ($temp['user_name'] != '' && !isExists('/' . $temp['user_name'])) {
		$db_connect = mysql_connect('192.168.27.1', 'root', '123456') or die("Unable to connect to the MySQL!");
		mysql_select_db('github', $db_connect);
		mysql_query('set names utf8;');
		$sql = "INSERT INTO `userinfo` (`full_name`, `user_name`, `works_for`, `home_location`, `email`, `url`, `join_date`, `followers`, `starred`, `following`, `contributions`, `longest_streak`, `current_streak`, `create_date`) VALUES ('" . $temp['full_name'] . "', '" . $temp['user_name'] . "', '" . $temp['works_for'] . "', '" . $temp['home_location'] . "', '" . $temp['email'] . "', '" . $temp['url'] . "', '" . $temp['join_date'] . "', '" . $temp['followers'] . "', '" . $temp['starred'] . "', '" . $temp['following'] . "', '" . $temp['contributions'] . "', '" . $temp['longest_streak'] . "', '" . $temp['current_streak'] . "', '" . date('Y-m-d H:i:s') . "');";
		mysql_query($sql, $db_connect);

		$redis = new Redis();
		$redis->connect('127.0.0.1', 6379);
		$redis->set('/' . $temp['user_name'], json_encode($temp, JSON_UNESCAPED_UNICODE));
		echo $k . ': ' . $temp['user_name'] . " saved!\n";
	}
}

function isExists($url) {
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	return $redis->exists($url);
}

function matchNextPage($result) {
	$rule = '/<a href\=\"(.+)\" rel\=\"nofollow\">Next<\/a>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchFullName($result) {
	$rule = '/<span class\=\"vcard-fullname\" itemprop\=\"name\">(.+)<\/span>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchUserName($result) {
	$rule = '/<span class\=\"vcard-username\" itemprop\=\"additionalName\">(.+)<\/span>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchWorksFor($result) {
	$rule = '/m3-1h-1v3h-1V7h2v3z\"><\/path><\/svg>(.+)<\/li>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchHomeLocation($result) {
	$rule = '/0.89 2 2z\"><\/path><\/svg>(.+)<\/li>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchEmail($result) {
	$rule = '/<a class\=\"email\" href=\".+\">(.+)<\/a>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchUrl($result) {
	$rule = '/<a href\=\".+\" class\=\"url\" rel\=\".+\">(.+)<\/a>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchJoinDate($result) {
	$rule = '/<time class\=\"join-date\" datetime\=\"(.+)\" day\=\"numeric\"/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : '';
}

function matchFSF($result) {
	$rule = '/<strong class\=\"vcard-stat-count\">(.+)<\/strong>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1]) ? $preg[1] : array(0, 0, 0);
}

function matchContributions($result) {
	$rule = '/<span class\=\"contrib-number\">(\d+) total<\/span>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1][0]) ? $preg[1][0] : 0;
}

function matchStreak($result) {
	$rule = '/<span class\=\"contrib-number\">(\d+) d.+<\/span>/';
	preg_match_all($rule, $result, $preg);
	return isset($preg[1]) ? $preg[1] : array(0, 0, 0);
}