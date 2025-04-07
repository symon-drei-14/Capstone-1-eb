<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app/.json?auth=Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7"; 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $firebase_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo json_encode([
        "error" => "cURL Error: " . curl_error($ch)
    ]);
    exit;
}

curl_close($ch);

echo $response;
?>