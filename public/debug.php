<?php
// debug.php - отладочный файл для проверки параметров

echo "<h2>Отладочная информация</h2>";
echo "<h3>GET параметры:</h3>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h3>POST параметры:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h3>REQUEST параметры:</h3>";
echo "<pre>" . print_r($_REQUEST, true) . "</pre>";

echo "<h3>Заголовки:</h3>";
echo "<pre>" . print_r(getallheaders(), true) . "</pre>";

echo "<h3>URL запроса:</h3>";
echo "<pre>" . $_SERVER['REQUEST_URI'] . "</pre>";

echo "<h3>QUERY_STRING:</h3>";
echo "<pre>" . $_SERVER['QUERY_STRING'] . "</pre>";
?> 