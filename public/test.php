<?php
// test.php - тестовый файл для проверки работы

echo "<h2>Тест приложения LED Calculator Widget</h2>";

echo "<h3>Параметры запроса:</h3>";
echo "<pre>" . print_r($_REQUEST, true) . "</pre>";

echo "<h3>Определение типа запроса:</h3>";

$hasPlacement = isset($_REQUEST['PLACEMENT']);
$placementValue = $_REQUEST['PLACEMENT'] ?? 'не установлен';
$hasEvent = isset($_REQUEST['event']);
$eventValue = $_REQUEST['event'] ?? 'не установлен';

echo "PLACEMENT: $placementValue<br>";
echo "event: $eventValue<br>";

if ($hasPlacement && $_REQUEST['PLACEMENT'] === 'CRM_DEAL_DETAIL_ACTIVITY') {
    echo "<p style='color: green;'>Это placement call для калькулятора - должен идти на index.php</p>";
} elseif (!$hasPlacement || ($hasEvent && $_REQUEST['event'] === 'ONAPPINSTALL')) {
    echo "<p style='color: blue;'>Это установка приложения - должен идти на install.php</p>";
} else {
    echo "<p style='color: red;'>Неизвестный тип запроса</p>";
}

echo "<h3>Рекомендуемые действия:</h3>";
echo "<ul>";
echo "<li><a href='install.php'>Проверить install.php</a></li>";
echo "<li><a href='index.php'>Проверить index.php</a></li>";
echo "<li><a href='debug.php'>Отладочная информация</a></li>";
echo "</ul>";
?> 