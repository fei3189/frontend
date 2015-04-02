<?php
$url = $_REQUEST['url'];
if ($url === null) {
  header('HTTP/1.1 400 BAD REQUEST', true, 400);
  die();
}
if (substr($url, 0, 1) !== '/') {
  header('HTTP/1.1 400 BAD REQUEST', true, 400);
  die();
}
$params = $_REQUEST['params'];
if ($params !== null) {
  $url = $url . '?' . http_build_query($params);
}
header('Content-Type: application/json');
echo file_get_contents('http://10.0.17.154:8181' . $url);
?>
