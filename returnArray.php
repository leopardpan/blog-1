<?php
header('Content-type: application/json');
$data = array (
    "token" => "swdadwadawdawdawdadadawwdawdaw",
    "askcontent" => array(
    	"title" => "主持人", 
    	"content" => "你好，我背单词能背住，但几天就忘，重复记忆之后，在阅读时，容易把相近单词的意思记混……我该怎么做呢？"
    	),
    "imgs" =>array(

        "uir1",
        "uir1",
        "uir1",
        "uir1",
        "uir1"

        )
);

print_r(json_encode($data));
?>