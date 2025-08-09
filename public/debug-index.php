<?php
// debug-index.php - УПРОЩЕННАЯ ВЕРСИЯ ДЛЯ ОТЛАДКИ
echo "DEBUG: Script started\n";

// Заголовки для работы во фрейме Битрикс24
header('X-Frame-Options: ALLOWALL');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo "DEBUG: Headers set\n";

// Показываем параметры запроса
echo "DEBUG: Request parameters:\n";
print_r($_REQUEST);

echo "DEBUG: Creating iframe with target URL\n";

// Прямо возвращаем iframe без всякой логики
$targetUrl = 'https://dimpin-app.store/apps/led-calculator/';
echo '<!DOCTYPE html><html><head><title>DEBUG LED калькулятор</title></head><body>';
echo '<h2>DEBUG MODE</h2>';
echo '<p>Target URL: ' . htmlspecialchars($targetUrl) . '</p>';
echo '<iframe src="' . htmlspecialchars($targetUrl) . '" width="100%" height="600px" frameborder="0"></iframe>';
echo '</body></html>';
