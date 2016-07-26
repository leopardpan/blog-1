<?php 
header('Content-type: application/json');

//if (!defined("ACCESSTOKEN")) define("ACCESSTOKEN","123456");

//$token=$_GET["access_token"];

//if (empty($token)||$token!=ACCESSTOKEN) exit(1);

// $name = isset($_GET['name'])? $_GET['name']:"无名氏";
// $data = array('name' => $name );

    $json='{"data":
[
{
"id": "150525102207b041458d539380d91adacf",
"yyyappid": "wx33dc1a5264b4e846",
"token": "354e6b14b65b79ad",
"dkid": "160621113635ce445bad978234237df677",
"askerid": "yy_oxWE2swWz8bdVnz2Q_D-8KsyGy70_0",
"askernickname": "yanjie",
"askerheadimg": "http://wx.qlogo.cn/mmopen/cMWgu6Gd4lydtyrvAejHzvg9fM0a7gtD5k0nZ6mNAud8sDfY7WictJWAPHaPOravNpCPBicpIbHK4yvLlHaYUWNoJmaFfcIQ29/96",
"askerjibie": "",
"askcontent": "你好，我背单词能背住，但几天就忘，重复记忆之后，在阅读时，容易把相近单词的意思记混……我该怎么做呢？",
"askcontenttype": "image",
"askfiles": [
{
"type": "image",
"url": "http://q.cdn.mtq.tvm.cn/wtopic/image/20160622/2016062211063660b30a941553d5542ac7fcd_tvm.jpg"
}
],
"answererid": "vu_ 160621113635ce445bad978234237df677_0",
"answerernickname": "春妮",
"answererheadimg": "http://qa.wsq.mtq.tvm.cn/wtopic/image/20160621/2016062123484070d410cf155533824d97f0e_tvm.jpg",
"answererjibie": "主持人",
"answercontent": "",
"answercontenttype": "audio",
"answerfiles": [
{
"times": 6,
"type": "audio",
"url": "http://qa.wsq.mtq.tvm.cn/wtopic/audio/20160620/2016062016164670d410cf155533824d97f36.mp3"
}
],
"answer_time": "2016-06-20 16:16:49",
"answer_timestamp": 1466410609830,
"answerstatus":1,
"listen_count": 0,
"zan_count": 0,
"expect_count": 0,
"is_free": 0,
"offer": 1000,
"payunit": 0,
"create_time": "2016-06-20 16:16:49",
"create_timestamp": 1466410609830,
"update_time": "2016-06-20 16:16:49",
"update_timestamp": 1466410609830
}
,
{
"id": "150525102207b041458d539380d91adacf",
"yyyappid": "wx33dc1a5264b4e846",
"token": "354e6b14b65b79ad",
"dkid": "160621113635ce445bad978234237df677",
"askerid": "yy_oxWE2swWz8bdVnz2Q_D-8KsyGy70_0",
"askernickname": "yanjie",
"askerheadimg": "http://wx.qlogo.cn/mmopen/cMWgu6Gd4lydtyrvAejHzvg9fM0a7gtD5k0nZ6mNAud8sDfY7WictJWAPHaPOravNpCPBicpIbHK4yvLlHaYUWNoJmaFfcIQ29/96",
"askerjibie": "",
"askcontent": "有没有女生因为你英语好而投怀送抱，被你潜的？学好英语是不是方便泡妞？",
"askcontenttype": "text",
"askfiles": [],
"answererid": "vu_ 160621113635ce445bad978234237df677_0",
"answerernickname": "春妮",
"answererheadimg": "http://qa.wsq.mtq.tvm.cn/wtopic/image/20160621/2016062123484070d410cf155533824d97f0e_tvm.jpg",
"answererjibie": "主持人",
"answercontent": "",
"answercontenttype": "audio",
"answerfiles": [
{
"times": 6,
"type": "audio",
"url": "http://qa.wsq.mtq.tvm.cn/wtopic/audio/20160620/2016062016164670d410cf155533824d97f36.mp3"
}
],
"answer_time": "2016-06-20 16:16:49",
"answer_timestamp": 1466410609830,
"answerstatus":1,
"listen_count": 0,
"zan_count": 0,
"expect_count": 0,
"is_free": 0,
"offer": 1000,
"payunit": 0,
"create_time": "2016-06-20 16:16:49",
"create_timestamp": 1466410609830,
"update_time": "2016-06-20 16:16:49",
"update_timestamp": 1466410609830
}
]
}';
    echo $json;
    exit(0);
?>