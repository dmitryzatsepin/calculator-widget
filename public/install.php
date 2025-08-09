<?php
// install.php (Для SDK 1.5.0)

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

// Проверяем, что это действительно установка
// Установка определяется:
// 1. Отсутствием PLACEMENT
// 2. PLACEMENT=DEFAULT (переустановка)
// 3. Наличием event=ONAPPINSTALL
// 4. Наличием всех обязательных параметров установки
$isInstallation = !isset($_REQUEST['PLACEMENT']) || 
                  $_REQUEST['PLACEMENT'] === 'DEFAULT' ||
                  (isset($_REQUEST['event']) && $_REQUEST['event'] === 'ONAPPINSTALL') ||
                  (isset($_REQUEST['member_id']) && isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']));

if (!$isInstallation) {
    http_response_code(400);
    echo 'This script is for installation only. Received params: ' . print_r($_REQUEST, true);
    exit;
}

// 1. Настройка логгера
$logger = new Logger('b24-install-log');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
$logger->info('Install script started.', ['request_params' => $_REQUEST]);

try {
    // 2. Проверка обязательных параметров
    if (empty($_REQUEST['member_id']) || empty($_REQUEST['AUTH_ID']) || empty($_REQUEST['DOMAIN'])) {
        $logger->error('Missing required installation parameters', ['request' => $_REQUEST]);
        throw new \InvalidArgumentException('Missing required installation parameters: member_id, AUTH_ID, DOMAIN');
    }

    // Проверяем статус лицензии (если передан)
    if (isset($_REQUEST['status']) && $_REQUEST['status'] !== 'L') {
        $logger->warning('Installation with non-licensed status', ['status' => $_REQUEST['status']]);
    }

    // 3. Создаем Credentials (для версии 1.5.0)
    $authExpires = isset($_REQUEST['AUTH_EXPIRES']) ? (time() + (int)$_REQUEST['AUTH_EXPIRES']) : (time() + 3600);
    $authToken = new AuthToken(
        $_REQUEST['AUTH_ID'],      // Токен доступа
        $_REQUEST['REFRESH_ID'] ?? null, // Токен обновления
        $authExpires               // Время истечения
    );

    $appProfile = new ApplicationProfile(
        $_REQUEST['member_id'],    // Client ID
        'dummy_secret',            // Client Secret (заглушка для установки)
        new Scope(['crm', 'user', 'placement']) // Права доступа
    );

    $credentials = Credentials::createFromOAuth(
        $authToken,
        $appProfile,
        $_REQUEST['DOMAIN']        // Домен портала
    );

    // 4. Инициализация ядра SDK
    $core = (new CoreBuilder())
        ->withLogger($logger)
        ->withCredentials($credentials)
        ->build();

    // 5. Отвязываем старое размещение (если было)
    $handlerUrl = 'https://dimpin-app.store/apps/led-calculator-widget/public/index.php';
    $placementService = new Placement($core, $logger);
    
    try {
        $unbindResult = $placementService->unbind('CRM_DEAL_DETAIL_ACTIVITY', $handlerUrl);
        $logger->info('Placement unbound', ['result' => $unbindResult->getCoreResponse()->getResponseData()->getResult()]);
    } catch (\Exception $e) {
        $logger->notice('Unbind failed (placement may not exist)', ['error' => $e->getMessage()]);
    }

    // 6. Привязываем новое размещение с передачей ID сделки в options
    $bindResult = $placementService->bind(
        'CRM_DEAL_DETAIL_ACTIVITY',
        $handlerUrl,
        [
            'ru' => [
                'TITLE' => 'LED Калькулятор',
                'DESCRIPTION' => 'Расчет стоимости светодиодной продукции'
            ]
        ],
        ['ID' => '{{ID}}'] // Передаем ID сделки в placement options
    );

    if (!$bindResult->isSuccess()) {
        $error = $bindResult->getCoreResponse()->getResponseData()->getError();
        $logger->error('Failed to bind placement', ['error' => $error]);
        throw new \RuntimeException('Failed to bind placement: ' . ($error['error_description'] ?? 'Unknown error'));
    }

    $logger->info('Placement bound successfully', [
        'result' => $bindResult->getCoreResponse()->getResponseData()->getResult()
    ]);
?>
<!-- 7. Отображаем страницу завершения установки -->
    <!DOCTYPE html>
        <html lang="ru">
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="//api.bitrix24.com/api/v1/"></script>
        <title>Установка завершена</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #333;
            }
            
            .container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 48px;
                text-align: center;
                max-width: 480px;
                width: 90%;
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .success-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #4CAF50, #45a049);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .success-icon::after {
                content: "✓";
                color: white;
                font-size: 40px;
                font-weight: bold;
            }
            
            h1 {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 16px;
            }
            
            .subtitle {
                color: #7f8c8d;
                font-size: 16px;
                line-height: 1.5;
                margin-bottom: 32px;
            }
            
            .close-btn {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                padding: 12px 32px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }
            
            .close-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }
            
            .brand {
                background-color:rgb(205, 203, 203);
                margin-top: 24px;
                padding: 10px;
                border-radius: 10px;
                font-size: 14px;
                color: #bdc3c7;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-icon"></div>
            <h1>Установка завершена!</h1>
            <p class="subtitle">Приложение успешно интегрировано в ваш Битрикс24. 
            <p class="subtitle">Для запуска калькулятора нажмите на кнопку "Запустить LED калькулятор" в меню таймлайна.</p>
            <div class="brand">
                <a href="https://dimpin-app.store/apps/led-calculator/" target="_blank">
                    <img src="https://cdn-ru.bitrix24.ru/b29272776/disk/c50/c5013cb925a4e55484a4f67d0d1523da/73e120f5e9f3cc8a95cb600a8fd0b918.png" alt="LEDTS">
                </a>
            </div>
        </div>
    </body>
    <script>
        BX24.init(function(){
            BX24.installFinish();
        });
    </script>
</html>
<?php
    $logger->info('Installation completed successfully');

} catch (\Throwable $e) {
    $errorMessage = 'Install Error: ' . $e->getMessage();
    $logger->error($errorMessage, ['trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
}
?>