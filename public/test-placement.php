<?php
// test-placement.php - тест placement с разными параметрами

echo "<h2>Тест Placement</h2>";

// Тест 1: Установка (без PLACEMENT)
echo "<h3>Тест 1: Установка (без PLACEMENT)</h3>";
$installParams = [
    'DOMAIN' => 'ledts.bitrix24.ru',
    'AUTH_ID' => '275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d',
    'member_id' => '2ed3b3005a7c2a8f82e51326f960a106',
    'status' => 'L'
];
echo "<p><a href='index.php?" . http_build_query($installParams) . "' target='_blank'>Тест установки</a></p>";

// Тест 2: Placement DEFAULT (как сейчас приходит)
echo "<h3>Тест 2: Placement DEFAULT</h3>";
$defaultParams = [
    'DOMAIN' => 'ledts.bitrix24.ru',
    'AUTH_ID' => '275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d',
    'member_id' => '2ed3b3005a7c2a8f82e51326f960a106',
    'PLACEMENT' => 'DEFAULT',
    'PLACEMENT_OPTIONS' => '{"any":"130/"}'
];
echo "<p><a href='index.php?" . http_build_query($defaultParams) . "' target='_blank'>Тест DEFAULT placement</a></p>";

// Тест 3: Placement CRM_DEAL_DETAIL_ACTIVITY (как должно быть)
echo "<h3>Тест 3: Placement CRM_DEAL_DETAIL_ACTIVITY</h3>";
$dealParams = [
    'DOMAIN' => 'ledts.bitrix24.ru',
    'AUTH_ID' => '275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d',
    'member_id' => '2ed3b3005a7c2a8f82e51326f960a106',
    'PLACEMENT' => 'CRM_DEAL_DETAIL_ACTIVITY',
    'PLACEMENT_OPTIONS' => '{"ID":"123"}'
];
echo "<p><a href='index.php?" . http_build_query($dealParams) . "' target='_blank'>Тест CRM_DEAL_DETAIL_ACTIVITY placement</a></p>";

// Тест 4: Проверка логов
echo "<h3>Тест 4: Проверка логов</h3>";
echo "<p><a href='logs/app.log' target='_blank'>Посмотреть логи</a></p>";

echo "<h3>Информация о placement:</h3>";
echo "<p>Согласно логам, placement был создан с параметрами:</p>";
echo "<ul>";
echo "<li>PLACEMENT: CRM_DEAL_DETAIL_ACTIVITY</li>";
echo "<li>HANDLER: https://dimpin-app.store/apps/led-calculator-widget/public/index.php</li>";
echo "<li>OPTIONS: {\"ID\":\"{{ID}}\"}</li>";
echo "</ul>";

echo "<p>Но при запуске приходит PLACEMENT=DEFAULT, что означает, что кнопка не привязана к карточке сделки.</p>";
?> 