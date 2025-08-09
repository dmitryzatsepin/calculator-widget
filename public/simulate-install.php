<?php
// simulate-install.php - эмуляция установки приложения

echo "<h2>Эмуляция установки приложения</h2>";

// Параметры как при установке
$_REQUEST['DOMAIN'] = 'ledts.bitrix24.ru';
$_REQUEST['PROTOCOL'] = '1';
$_REQUEST['LANG'] = 'ru';
$_REQUEST['APP_SID'] = '3524253b7cf8342ccac812340626fcd2';
$_REQUEST['AUTH_ID'] = '275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d';
$_REQUEST['AUTH_EXPIRES'] = '3600';
$_REQUEST['REFRESH_ID'] = '18debd68007b0afe006cd3c800000302201c071272a902fdfb56cc21d738c04352addb';
$_REQUEST['member_id'] = '2ed3b3005a7c2a8f82e51326f960a106';
$_REQUEST['status'] = 'L';

echo "<h3>Параметры установки:</h3>";
echo "<pre>" . print_r($_REQUEST, true) . "</pre>";

echo "<h3>Тестирование index.php:</h3>";
echo "<p><a href='index.php?" . http_build_query($_REQUEST) . "' target='_blank'>Открыть index.php с параметрами установки</a></p>";

echo "<h3>Тестирование install.php:</h3>";
echo "<p><a href='install.php?" . http_build_query($_REQUEST) . "' target='_blank'>Открыть install.php с параметрами установки</a></p>";

echo "<h3>Проверка статуса:</h3>";
echo "<p><a href='status.php' target='_blank'>Проверить статус сервера</a></p>";
?> 