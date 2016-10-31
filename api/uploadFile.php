<?php
require_once 'PHP_AES_CBC.php';
require_once 'aliyun-oss-php-sdk.phar';
use OSS\OssClient;
use OSS\Core\OssException;

$conf = include 'config.php';

//OSS配置
$ossConf = $conf['oss'];

//数据库配置
$dbConf = $conf['db'];

//获取参数
$uid = isset($_POST["uid"]) ? addslashes($_POST["uid"]) : '';
$jx3pve = isset($_POST["jx3pve"]) ? addslashes(urldecode($_POST['jx3pve'])) : '';
//echo $uid;
//echo $jx3pve.'|';

//验证参数是否完整
if (!$uid || !$jx3pve || strlen($jx3pve) != 24) {
    echo "[上传失败-非法请求1, 0]";
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
    echo "[上传失败-非法请求2, 0]";
    die();
}

//目标路径
$file_path = dirname(__FILE__) . '/../data/' . $uid;
$file_name = $file_path . '/' . $file_time . '.xml';
//确保路径存在，不存在则创建
ensurePath($file_path);

//移动临时文件到目标路径
$result = move_uploaded_file($_FILES['userfile']['tmp_name'], $file_name);
if ((bool)$result) {
    try {
        $ossClient = new OssClient($ossConf['accessKeyId'], $ossConf['accessKeySecret'], $ossConf['endpoint']);
        //Upload File To OSS
        $object = $uid . "/keypress/" . $file_time . ".xml";
        $ossClient->uploadFile($ossConf['bucket'], $object, $file_name);
        $exist = $ossClient->doesObjectExist($ossConf['bucket'], $object);
        if ($exist) {
            $conn = new mysqli($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['dbname']);
            $sql = "INSERT INTO `keypress` (uid, last_set_time) VALUES({$uid},{$file_time})";
            $res = $conn->query($sql);
            if ($res) {
                echo "[上传成功, 1]";
                $conn->close();
                die();
            }
        } else {
            echo "[上传失败-非法请求3, 0]";
            die();
        }
    } catch (OssException $e) {
        print($e->getMessage() . "\n");
        return;
    }
} else {
    echo "[上传失败-非法请求4, 0]";
    die();
}

//确认存在路径，没有则新建
function ensurePath($path)
{
    str_replace('\\', '/', $path);
    $dirArray = explode('/', $path);
    $dirString = '';
    foreach ($dirArray as $dirName) {
        $dirString .= $dirName . '/';
        @mkdir($dirString);
    }
    return 1;
}

