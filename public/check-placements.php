<?php
// check-placements.php - проверка существующих placements

require_once __DIR__ . '/../vendor/autoload.php';

use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\CoreBuilder;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Services\Placement\Service\Placement;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

echo "<h2>Проверка Placements</h2>";

// Параметры для подключения
$authToken = new AuthToken(
    '275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d',
    '18debd68007b0afe006cd3c800000302201c071272a902fdfb56cc21d738c04352addb',
    time() + 3600
);

$appProfile = new ApplicationProfile(
    '2ed3b3005a7c2a8f82e51326f960a106',
    'dummy_secret',
    new Scope(['crm', 'user', 'placement'])
);

$credentials = Credentials::createFromOAuth(
    $authToken,
    $appProfile,
    'ledts.bitrix24.ru'
);

try {
    // Инициализация ядра SDK
    $logger = new Logger('b24-check-log');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/check.log', Logger::DEBUG));
    
    $core = (new CoreBuilder())
        ->withLogger($logger)
        ->withCredentials($credentials)
        ->build();

    $placementService = new Placement($core, $logger);

    echo "<h3>Проверка placement CRM_DEAL_DETAIL_ACTIVITY:</h3>";
    
    // Попробуем получить информацию о placement
    try {
        // Проверим, есть ли placement
        $handlerUrl = 'https://dimpin-app.store/apps/led-calculator-widget/public/index.php';
        
        echo "<p>Проверяем placement для: $handlerUrl</p>";
        
        // Попробуем создать placement заново
        $bindResult = $placementService->bind(
            'CRM_DEAL_DETAIL_ACTIVITY',
            $handlerUrl,
            [
                'ru' => [
                    'TITLE' => 'LED Калькулятор',
                    'DESCRIPTION' => 'Расчет стоимости светодиодной продукции'
                ]
            ],
            ['ID' => '{{ID}}']
        );

        if ($bindResult->isSuccess()) {
            echo "<p style='color: green;'>Placement успешно создан!</p>";
        } else {
            echo "<p style='color: red;'>Ошибка создания placement: " . print_r($bindResult->getErrorMessages(), true) . "</p>";
        }
        
    } catch (\Exception $e) {
        echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
    }

} catch (\Exception $e) {
    echo "<p style='color: red;'>Ошибка инициализации: " . $e->getMessage() . "</p>";
}

echo "<h3>Рекомендации:</h3>";
echo "<ul>";
echo "<li>Убедитесь, что приложение установлено с правами: crm, user, placement</li>";
echo "<li>Проверьте, что в настройках приложения указан правильный URL</li>";
echo "<li>Попробуйте переустановить приложение</li>";
echo "</ul>";

echo "<p><a href='test-placement.php'>Вернуться к тестам</a></p>";
?> 