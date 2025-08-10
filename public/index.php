<?php
// index.php - МАКСИМАЛЬНО ЛЁГКИЙ ОБРАБОТЧИК РАЗМЕЩЕНИЯ

// ОТЛАДКА - записываем в лог что приходит
error_log('INDEX.PHP CALLED with params: ' . print_r($_REQUEST, true));
error_log('INDEX.PHP Headers: ' . print_r(getallheaders(), true));

// Проверяем сессию установки
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$installCompleted = isset($_SESSION['install_completed']) && $_SESSION['install_completed'] === true;
$installTime = $_SESSION['install_time'] ?? 0;

// Дополнительная проверка через cookies
$cookieInstallCompleted = isset($_COOKIE['b24_install_completed']) && $_COOKIE['b24_install_completed'] === 'true';
$cookieInstallTime = isset($_COOKIE['b24_install_time']) ? (int)$_COOKIE['b24_install_time'] : 0;

// Устанавливаем флаг установки если есть в cookies
if ($cookieInstallCompleted && !$installCompleted) {
    $installCompleted = true;
    $installTime = $cookieInstallTime;
    $_SESSION['install_completed'] = true;
    $_SESSION['install_time'] = $installTime;
}

error_log('INDEX.PHP Session check - install_completed: ' . ($installCompleted ? 'true' : 'false') . ', install_time: ' . $installTime);
error_log('INDEX.PHP Cookie check - install_completed: ' . ($cookieInstallCompleted ? 'true' : 'false') . ', install_time: ' . $cookieInstallTime);

// Заголовки для работы во фрейме Битрикс24
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Security-Policy: frame-ancestors *;');

// ПОЛУЧАЕМ ID СДЕЛКИ ИЗ PLACEMENT_OPTIONS (если есть)
$dealId = null;
if (isset($_REQUEST['PLACEMENT_OPTIONS']) && !empty($_REQUEST['PLACEMENT_OPTIONS'])) {
    $placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
    if (isset($placementOptions['ID']) && !empty($placementOptions['ID']) && $placementOptions['ID'] !== '{{ID}}') {
        $dealId = $placementOptions['ID'];
        error_log('INDEX.PHP: Got deal ID from PLACEMENT_OPTIONS: ' . $dealId);
    }
}

// ЕСЛИ ЭТО УСТАНОВКА, ПРОВЕРКА ИЛИ АВТОМАТИЧЕСКАЯ ПРОВЕРКА РАЗМЕЩЕНИЯ - НЕ ПОКАЗЫВАЕМ КАЛЬКУЛЯТОР
if (empty($_REQUEST) || 
    (isset($_REQUEST['ID']) && $_REQUEST['ID'] === '{{ID}}') ||
    (isset($_REQUEST['install_check']) && $_REQUEST['install_check'] === 'Y') ||
    (isset($_REQUEST['event']) && $_REQUEST['event'] === 'ONAPPINSTALL') ||
    (isset($_REQUEST['member_id']) && isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']) && !isset($_REQUEST['PLACEMENT'])) ||
    // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА: если это автоматическая проверка размещения после установки
    (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'CRM_DEAL_DETAIL_ACTIVITY' && 
     (!isset($_REQUEST['ID']) || $_REQUEST['ID'] === '{{ID}}' || empty($_REQUEST['ID'])) && !$dealId) ||
    // НОВАЯ ПРОВЕРКА: если это вызов после установки с параметрами установки
    (isset($_REQUEST['install_finished']) && $_REQUEST['install_finished'] === 'Y') ||
    // ПРОВЕРКА: если это тестовый вызов без реального ID сделки
    (isset($_REQUEST['PLACEMENT']) && !isset($_REQUEST['ID']) && !$dealId) ||
    // ПРОВЕРКА: если это проверка готовности размещения
    (isset($_REQUEST['placement_ready']) && $_REQUEST['placement_ready'] === 'Y') ||
    // ПРОВЕРКА: если это вызов от установщика или проверки
    (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Bitrix24') !== false && 
     (!isset($_REQUEST['PLACEMENT']) || (empty($_REQUEST['ID']) && !$dealId) || $_REQUEST['ID'] === '{{ID}}')) ||
    // ПРОВЕРКА: если это вызов сразу после установки (в течение 5 минут)
    (isset($_REQUEST['member_id']) && isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']) && 
     (!isset($_REQUEST['PLACEMENT']) || (empty($_REQUEST['ID']) && !$dealId))) ||
    // ПРОВЕРКА: если это вызов с параметрами установки
    (isset($_REQUEST['event']) && $_REQUEST['event'] === 'ONAPPINSTALL') ||
    // ПРОВЕРКА: если установка была завершена недавно (в течение 10 минут)
    ($installCompleted && (time() - $installTime) < 600) ||
    // ПРОВЕРКА: если установка была завершена недавно через cookies (в течение 10 минут)
    ($cookieInstallCompleted && (time() - $cookieInstallTime) < 600) ||
    // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА: если это вызов без реального ID сделки
    ((!isset($_REQUEST['ID']) || $_REQUEST['ID'] === '{{ID}}' || empty($_REQUEST['ID'])) && !$dealId)) {
    
    error_log('INDEX.PHP: Showing empty page for installation/check/auto-verification');
    // Показываем пустую страницу для проверки размещения и установки
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Ready</title></head><body><div style="display:none;">Installation check - showing empty page</div><script>console.log("Installation check page shown");</script></body></html>';
    exit;
}

error_log('INDEX.PHP: This is a real placement call with real deal ID, showing calculator');
error_log('INDEX.PHP: Deal ID: ' . ($dealId ?? $_REQUEST['ID'] ?? 'NOT_SET') . ', Placement: ' . ($_REQUEST['PLACEMENT'] ?? 'NOT_SET'));

// Проверяем автозагрузчик
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('ERROR: vendor/autoload.php not found');
}

require_once __DIR__ . '/../vendor/autoload.php';

use Bitrix24\SDK\Core\ApiClient;
use Bitrix24\SDK\Core\ApiLevelErrorHandler;
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Infrastructure\HttpClient\RequestId\DefaultRequestIdGenerator;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\HttpClient;

try {
    // Создаем Credentials
    $authToken = new AuthToken($_REQUEST['AUTH_ID'], $_REQUEST['REFRESH_ID'] ?? null, time() + 3600);
    $appProfile = new ApplicationProfile($_REQUEST['member_id'], 'dummy_secret', new Scope(['crm', 'user', 'placement']));
    $credentials = Credentials::createFromOAuth($authToken, $appProfile, $_REQUEST['DOMAIN']);

    // Инициализация SDK
    $apiClient = new ApiClient($credentials, HttpClient::create(), new DefaultRequestIdGenerator(), new ApiLevelErrorHandler(new Logger('dummy')), new Logger('dummy'));
    $core = new Core($apiClient, new ApiLevelErrorHandler(new Logger('dummy')), new EventDispatcher(), new Logger('dummy'));

    // Получаем ID сделки (приоритет: PLACEMENT_OPTIONS -> $_REQUEST['ID] -> demo)
    $finalDealId = $dealId ?? $_REQUEST['ID'] ?? $_REQUEST['id'] ?? 'demo';
    
    // Передаем параметры в калькулятор
    $queryParams = http_build_query([
        'dealId' => $finalDealId, 
        'userId' => 'current',
        'domain' => $_REQUEST['DOMAIN'] ?? 'unknown.bitrix24.ru', 
        'authId' => $_REQUEST['AUTH_ID'] ?? 'demo_auth', 
        'memberId' => $_REQUEST['member_id'] ?? 'demo_member'
    ]);
    
    error_log('INDEX.PHP: Showing calculator with params: ' . $queryParams);
    
    // Показываем калькулятор в iframe ТОЛЬКО при реальном вызове из карточки сделки
    echo '<!DOCTYPE html><html><head><title>LED калькулятор</title><script src="//api.bitrix24.com/api/v1/"></script><style>html,body{margin:0;padding:0;height:100%;}iframe{border:0;width:100%;height:100%;}</style></head><body><iframe src="https://dimpin-app.store/apps/led-calculator/?' . htmlspecialchars($queryParams, ENT_QUOTES, 'UTF-8') . '" allow="scripts, forms" sandbox="allow-scripts allow-forms allow-same-origin"></iframe></body></html>';
    
} catch (Exception $e) {
    error_log('INDEX.PHP: Exception: ' . $e->getMessage());
    // Fallback - показываем калькулятор без параметров
    echo '<!DOCTYPE html><html><head><title>LED калькулятор</title></head><body>';
    echo '<iframe src="https://dimpin-app.store/apps/led-calculator/" width="100%" height="600px" frameborder="0" allow="scripts, forms" sandbox="allow-scripts allow-forms allow-same-origin"></iframe>';
    echo '</body></html>';
}
?>
