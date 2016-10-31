<?php
require_once 'PHP_AES_CBC.php';
$conf = include 'config.php';
//数据库配置
$dbConf = $conf['db'];

//获取参数
$uid = isset($_POST["uid"]) ? addslashes($_POST["uid"]) : '';
$jx3pve = isset($_POST["jx3pve"]) ? addslashes(urldecode($_POST['jx3pve'])) : '';
//echo $uid;
//echo $jx3pve.'|';

//验证参数是否完整
if (!$uid || !$jx3pve || strlen($jx3pve) != 24) {
    echo "[下载失败-非法请求1, 0]";
    die();
}
//替换.为+号
$jx3pve = str_replace(".", "+", $jx3pve);


//解密验证
$encryptObj = new MagicCrypt();
$decryptString = $encryptObj->decrypt($jx3pve);//解密
$file_time = substr($decryptString, 0, 10);
$time = time();//当前时间
$diff_time = $time - $file_time;//时间差
if ($diff_time > 25 || $diff_time < 0) {
    echo "[下载失败-非法请求2, 0]";
    die();
}

$conn = new mysqli($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['dbname']);
$sql = "SELECT last_set_time FROM `keypress` WHERE uid = {$uid} ORDER BY last_set_time DESC LiMIT 1";
$res = $conn->query($sql);
if ($res->num_rows > 0) {
    // 输出每行数据
    while($row = $res->fetch_assoc()) {
        $filename = $row['last_set_time'].'.xml';
        $file_path = "http://jx3pveos-data.jx3pve.com/".$uid."/keypress/".$filename;
        echo "[".$file_path.", 1]";
    }
    $conn->close();
    die();
} else {
    echo "[用户未曾上传过配置, 0]";
    die();
}
