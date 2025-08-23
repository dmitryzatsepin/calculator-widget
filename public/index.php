<?php

declare(strict_types=1);

use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\NullLogger;

require_once __DIR__ . '/../vendor/autoload.php';

// Проверяем наличие основных параметров
if (!isset($_REQUEST['PLACEMENT']) || 
    !isset($_REQUEST['AUTH_ID']) || 
    !isset($_REQUEST['member_id']) || 
    !isset($_REQUEST['DOMAIN'])) {
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><div style="padding:20px;text-align:center;"><h3>Доступ запрещен</h3><p>Недостаточно прав для доступа к калькулятору</p></div></body></html>';
    exit;
}

// Инициализируем профиль приложения согласно SDK
$appProfile = ApplicationProfile::initFromArray([
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => $_REQUEST['AUTH_ID'] ?? '',
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => $_REQUEST['REFRESH_ID'] ?? '',
    'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm,user,placement'
]);

try {
    // Создаем сервис с использованием SDK
    $b24Service = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
        Request::createFromGlobals(), 
        $appProfile,
        new EventDispatcher(),
        new NullLogger()
    );
    
} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body><div style="padding:20px;text-align:center;"><h3>Ошибка</h3><p>Не удалось подключиться к Bitrix24</p></div></body></html>';
    exit;
}

// Получаем ID сделки из PLACEMENT_OPTIONS
$dealId = null;
if (isset($_REQUEST['PLACEMENT_OPTIONS']) && !empty($_REQUEST['PLACEMENT_OPTIONS'])) {
    $placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
    if (isset($placementOptions['ID']) && !empty($placementOptions['ID']) && $placementOptions['ID'] !== '{{ID}}') {
        $dealId = $placementOptions['ID'];
    }
}

$finalDealId = $dealId ?? $_REQUEST['ID'] ?? 'demo';

// Заголовки для работы во фрейме Битрикс24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Security-Policy: frame-ancestors * https://ledts.bitrix24.ru https://*.bitrix24.ru;');
header('X-Frame-Options: ALLOWALL');
header('X-Content-Type-Options: nosniff');

// Передаем параметры в калькулятор
$queryParams = http_build_query([
    'dealId' => $finalDealId,
    'userId' => 'current',
    'domain' => $_REQUEST['DOMAIN'],
    'authId' => $_REQUEST['AUTH_ID'],
    'memberId' => $_REQUEST['member_id']
]);

// Показываем калькулятор в iframe
echo '<!DOCTYPE html>
<html>
<head>
    <title>LED Калькулятор</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .loading { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            background: #f5f5f5; 
        }
        .spinner { 
            width: 40px; 
            height: 40px; 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #3498db; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
            margin-right: 15px; 
        }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        iframe { 
            width: 100%; 
            height: 100vh; 
            border: none; 
            display: none; 
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div class="spinner"></div>
        <div>Идет загрузка приложения LED Калькулятор</div>
    </div>
    
    <iframe id="calculator-frame" src="https://dimpin-app.store/apps/led-calculator/index.html#' . htmlspecialchars($queryParams, ENT_QUOTES, 'UTF-8') . '" 
            allow="fullscreen"
            sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-presentation"
            style="display: none;">
    </iframe>
    
    <script>
        document.getElementById("calculator-frame").onload = function() {
            var loading = document.getElementById("loading");
            var iframe = document.getElementById("calculator-frame");
            
            if (loading) {
                loading.style.display = "none";
            }
            
            if (iframe) {
                iframe.style.display = "block";
            }
        };
    </script>
</body>
</html>';
?>
