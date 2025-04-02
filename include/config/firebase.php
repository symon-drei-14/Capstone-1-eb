<?php
$firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app/.json?auth=Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7"; // Change to your database path

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $firebase_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>
