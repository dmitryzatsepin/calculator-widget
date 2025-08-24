<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Symfony\Component\HttpFoundation\Request;

// Проверяем, есть ли токены для работы с SDK
if (!empty($_REQUEST['AUTH_ID']) && !empty($_REQUEST['REFRESH_ID'])) {
    $appProfile = ApplicationProfile::initFromArray([
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => 'local.68aadaa8104c18.56085418',
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => 'Ykd7eWTSzJIzS9lKa3S02Dc7UVhRW2f7gsbwr1oRzAmGc0W6Qg',
        'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm,user,placement'
    ]);

    try {
        $B24 = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
            Request::createFromGlobals(), 
            $appProfile
        );
    } catch (Exception $e) {
        $B24 = null;
    }
} else {
    $B24 = null;
}

$dealId = $_GET['deal_id'] ?? null;

// Инициализируем переменные по умолчанию
$currentUserId = 'unknown';
$currentUserName = 'Unknown User';
$dealTitle = null;

if ($B24 !== null) {
    try {
        $currentUser = $B24->core->call('user.current')->getResponseData()->getResult();
        $currentUserId = $currentUser['ID'];
        $currentUserName = $currentUser['NAME'] . ' ' . $currentUser['LAST_NAME'];
        
        if ($dealId) {
            $dealData = $B24->core->call('crm.deal.get', ['id' => $dealId])->getResponseData()->getResult();
            $dealTitle = $dealData['TITLE'];
        }
    } catch (Exception $e) {
        error_log('Bitrix24 SDK Error: ' . $e->getMessage());
    }
}

// Заголовки для работы во фрейме Битрикс24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Security-Policy: frame-ancestors * https://ledts.bitrix24.ru https://*.bitrix24.ru;');
header('X-Frame-Options: ALLOWALL');
header('X-Content-Type-Options: nosniff');

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LED Калькулятор</title>
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            font-family: Arial, sans-serif; 
            background: #f8f9fa;
        }
        .container {
            max-width: 100%;
            margin: 0;
            background: white;
            padding: 10px;
        }
        .info-section {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .calculator-frame {
            width: 100%;
            height: calc(100vh - 100px);
            border: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($dealId && $dealTitle): ?>
        <div class="info-section">
            <p><strong>Сделка:</strong> <?php echo htmlspecialchars($dealTitle); ?></p>
        </div>
        <?php endif; ?>
        
        <?php
        $iframeUrl = "https://dimpin-app.store/apps/led-calculator/?" . http_build_query([
            'dealId' => $dealId,
            'userId' => $currentUserId,
            'domain' => 'ledts.bitrix24.ru',
            'memberId' => 'current'
        ]);
        ?>
        <iframe id="calculator-frame" 
                class="calculator-frame"
                src="<?php echo htmlspecialchars($iframeUrl); ?>"
                allow="fullscreen"
                sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-presentation">
        </iframe>
    </div>
</body>
</html>