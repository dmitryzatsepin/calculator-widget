<?php
// install.php (Для SDK 1.5.0) - ОПТИМИЗИРОВАННАЯ ВЕРСИЯ

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

// ОБРАБОТКА POST-ЗАПРОСА ДЛЯ ВЕРИФИКАЦИИ УСТАНОВКИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install_verification') {
    $logger = new Logger('b24-install-verification');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    
    $verificationData = json_decode($_POST['verification_data'] ?? '{}', true);
    $logger->info('🔍 Client-side installation verification received', [
        'verification_data' => $verificationData,
        'install_completed' => $_POST['install_completed'] ?? false,
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Отправляем ответ клиенту
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Verification logged']);
    exit;
}

// ЛОГИРОВАНИЕ ВСЕХ ЗАПРОСОВ ДЛЯ ОТЛАДКИ
$debugLogger = new Logger('b24-install-debug');
$debugLogger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
$debugLogger->info('📥 Request received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'placement' => $_REQUEST['PLACEMENT'] ?? 'not_set',
    'deal_id' => $_REQUEST['ID'] ?? 'not_set',
    'has_member_id' => isset($_REQUEST['member_id']),
    'has_auth_id' => isset($_REQUEST['AUTH_ID']),
    'has_domain' => isset($_REQUEST['DOMAIN']),
    'all_params' => $_REQUEST
]);

// БЫСТРАЯ ПРОВЕРКА: если это обычный запуск калькулятора (не установка)
if (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'CRM_DEAL_DETAIL_ACTIVITY') {
    // Проверяем ID сделки в PLACEMENT_OPTIONS
    $placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'] ?? '{}', true);
    $dealId = $placementOptions['ID'] ?? null;
    
    // Если есть реальный ID сделки (не тестовый) - это запуск калькулятора
    if ($dealId && !empty($dealId) && $dealId !== '{{ID}}') {
        // Логируем перенаправление на калькулятор
        $logger = new Logger('b24-install-log');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
        $logger->info('🚀 Redirecting to calculator (real deal ID in PLACEMENT_OPTIONS)', [
            'placement' => $_REQUEST['PLACEMENT'],
            'deal_id' => $dealId,
            'placement_options' => $placementOptions,
            'has_member_id' => isset($_REQUEST['member_id']),
            'has_auth_id' => isset($_REQUEST['AUTH_ID']),
            'has_domain' => isset($_REQUEST['DOMAIN']),
            'all_params' => $_REQUEST
        ]);
        
        // Это обычный запуск калькулятора - перенаправляем на index.php
        $queryString = http_build_query($_REQUEST);
        $logger->info('Redirecting to index.php with query: ' . $queryString);
        header('Location: index.php?' . $queryString);
        exit;
    }
}



// БЫСТРАЯ ПРОВЕРКА УСТАНОВКИ
$isInstallation = 
    // Событие установки приложения
    (isset($_REQUEST['event']) && $_REQUEST['event'] === 'ONAPPINSTALL') ||
    // Первоначальная установка с обязательными параметрами (включая DOMAIN)
    (isset($_REQUEST['member_id']) && isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']) && 
     (!isset($_REQUEST['PLACEMENT']) || $_REQUEST['PLACEMENT'] === 'DEFAULT')) ||
    // Явный запрос на установку
    (isset($_REQUEST['install_finished']) && $_REQUEST['install_finished'] === 'Y') ||
    (isset($_REQUEST['placement_ready']) && $_REQUEST['placement_ready'] === 'Y') ||
    // Проверка на тестовый ID сделки (только для установки) - ОБЯЗАТЕЛЬНО с DOMAIN
    (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'CRM_DEAL_DETAIL_ACTIVITY' && 
     (!isset($_REQUEST['ID']) || $_REQUEST['ID'] === '{{ID}}' || empty($_REQUEST['ID'])) &&
     isset($_REQUEST['member_id']) && isset($_REQUEST['AUTH_ID']) && isset($_REQUEST['DOMAIN']));

// ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА: если это запуск калькулятора в карточке сделки - НЕ УСТАНОВКА!
if (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'CRM_DEAL_DETAIL_ACTIVITY') {
    $placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'] ?? '{}', true);
    $dealId = $placementOptions['ID'] ?? null;
    
    if ($dealId && !empty($dealId) && $dealId !== '{{ID}}') {
        $isInstallation = false; // Принудительно НЕ установка!
    }
}

if (!$isInstallation) {
    // Если это не установка - перенаправляем на index.php для обработки
    $logger = new Logger('b24-install-log');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    $logger->info('Not an installation request, redirecting to index.php', [
        'request_params' => $_REQUEST,
        'is_installation' => $isInstallation,
        'placement' => $_REQUEST['PLACEMENT'] ?? 'not_set',
        'has_id' => isset($_REQUEST['ID']) && !empty($_REQUEST['ID']) && $_REQUEST['ID'] !== '{{ID}}'
    ]);
    
    // Перенаправляем на index.php с теми же параметрами
    $queryString = http_build_query($_REQUEST);
    $logger->info('Redirecting to index.php with params: ' . $queryString);
    header('Location: index.php?' . $queryString);
    exit;
}

// Логгер
$logger = new Logger('b24-install-log');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
$logger->info('Install script started.', ['request_params' => $_REQUEST]);

// Проверяем, завершена ли уже установка
$placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'] ?? '{}', true);
$isAlreadyInstalled = isset($placementOptions['install_finished']) && $placementOptions['install_finished'] === 'Y';

if ($isAlreadyInstalled) {
    $logger->info('Installation already completed, showing completion page only');
    goto show_completion_page;
}

try {
    // Проверка обязательных параметров
    if (empty($_REQUEST['member_id']) || empty($_REQUEST['AUTH_ID']) || empty($_REQUEST['DOMAIN'])) {
        throw new \InvalidArgumentException('Missing required installation parameters');
    }

    // Создаем Credentials
    $authExpires = isset($_REQUEST['AUTH_EXPIRES']) ? (time() + (int)$_REQUEST['AUTH_EXPIRES']) : (time() + 3600);
    $authToken = new AuthToken(
        $_REQUEST['AUTH_ID'],
        $_REQUEST['REFRESH_ID'] ?? null,
        $authExpires
    );

    $appProfile = new ApplicationProfile(
        $_REQUEST['member_id'],
        'dummy_secret',
        new Scope(['crm', 'user', 'placement'])
    );

    $credentials = Credentials::createFromOAuth(
        $authToken,
        $appProfile,
        $_REQUEST['DOMAIN']
    );

    // Инициализация ядра SDK
    $core = (new CoreBuilder())
        ->withLogger($logger)
        ->withCredentials($credentials)
        ->build();
    
    $logger->info('Core SDK initialized successfully');

    // Отвязываем старое размещение
    $handlerUrl = 'https://dimpin-app.store/apps/led-calculator-widget/public/index.php';
    $placementService = new Placement($core, $logger);
    
    try {
        $placementService->unbind('CRM_DEAL_DETAIL_ACTIVITY', $handlerUrl);
        $logger->info('Old placement unbound');
    } catch (\Exception $e) {
        $logger->notice('Unbind failed (placement may not exist)');
    }

    // Привязываем новое размещение
    $bindResult = $placementService->bind(
        'CRM_DEAL_DETAIL_ACTIVITY',
        $handlerUrl,
        [
            'ru' => [
                'TITLE' => 'LED Калькулятор',
                'DESCRIPTION' => 'Расчет стоимости светодиодной продукции'
            ]
        ],
        [
            'ID' => '{{ID}}',
            'install_finished' => 'Y',
            'install_check' => 'Y',
            'placement_ready' => 'Y'
        ]
    );

    if (!$bindResult->isSuccess()) {
        $error = $bindResult->getCoreResponse()->getResponseData()->getError();
        throw new \RuntimeException('Failed to bind placement: ' . ($error['error_description'] ?? 'Unknown error'));
    }

    $logger->info('Placement bound successfully');
    
    // Устанавливаем флаг завершения установки
    $logger->info('Installation completed successfully, showing completion page');
    
    // Устанавливаем флаг завершения установки в сессии
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['install_completed'] = true;
    $_SESSION['install_time'] = time();
    
    // Устанавливаем флаг завершения установки в cookies
    setcookie('b24_install_completed', 'true', time() + 3600, '/');
    setcookie('b24_install_time', time(), time() + 3600, '/');
    
    // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА УСПЕШНОГО ЗАВЕРШЕНИЯ
    $logger->info('Installation verification started', [
        'session_install_completed' => $_SESSION['install_completed'] ?? false,
        'session_install_time' => $_SESSION['install_time'] ?? null,
        'cookies_set' => isset($_COOKIE['b24_install_completed']),
        'placement_options' => $placementOptions
    ]);
    
    // Проверяем, что все флаги установлены корректно
    $verificationChecks = [
        'session_flag' => $_SESSION['install_completed'] === true,
        'session_time' => isset($_SESSION['install_time']) && $_SESSION['install_time'] > 0,
        'placement_bound' => $bindResult->isSuccess(),
        'placement_options_set' => !empty($placementOptions)
    ];
    
    $logger->info('Installation verification results', $verificationChecks);
    
    // Если все проверки пройдены, логируем успешное завершение
    if (array_sum($verificationChecks) === count($verificationChecks)) {
        $logger->info('✅ INSTALLATION FULLY COMPLETED AND VERIFIED', [
            'all_checks_passed' => true,
            'verification_details' => $verificationChecks,
            'completion_timestamp' => date('Y-m-d H:i:s'),
            'request_id' => uniqid('install_', true)
        ]);
    } else {
        $logger->warning('⚠️ Installation completed but some verification checks failed', [
            'verification_details' => $verificationChecks,
            'failed_checks' => array_keys(array_filter($verificationChecks, function($check) { return !$check; }))
        ]);
    }

show_completion_page:
// Страница завершения установки - ТОЛЬКО ЗАВЕРШЕНИЕ, НЕ КАЛЬКУЛЯТОР
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <title>Установка завершена</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
            /* background-color: rgb(205, 203, 203); */
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
        <p class="subtitle">Приложение успешно интегрировано в ваш Битрикс24.</p>
        <p class="subtitle">Для запуска калькулятора нажмите на кнопку "LED Калькулятор" в меню таймлайна сделки.</p>
        <a href="https://ledts.bitrix24.ru/crm/deal/list/" target="_blank">
            <button class="close-btn">Перейти в сделки</button>
        </a>
        <div class="brand">
            <a href="https://dimpin-app.store/" rel="noopener noreferrer">
                <img src="https://cdn-ru.bitrix24.ru/b30896596/bitrix24/c82/c82175cad74850b46550b565bc98c38d/logo_biwaa2f5kc.png" alt="DIMPIN APP STORE">
            </a>
        </div>
    </div>
    <script>
        BX24.init(function(){
            <?php if (!$isAlreadyInstalled): ?>
            // Завершаем установку
            BX24.installFinish();
            console.log('Installation finished successfully');
            <?php endif; ?>
            
            // Добавляем обработчик для кнопки закрытия
            document.querySelector('.close-btn').addEventListener('click', function() {
                try {
                    BX24.closeApplication();
                } catch (e) {
                    console.log('Could not close app, user can close manually');
                }
            });
            
            // Логируем успешное завершение установки
            console.log('Installation completion page loaded successfully');
            
            // Устанавливаем флаг в localStorage для дополнительной проверки
            try {
                localStorage.setItem('b24_install_completed', 'true');
                localStorage.setItem('b24_install_time', Date.now().toString());
                
                // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА УСПЕШНОГО ЗАВЕРШЕНИЯ
                const installVerification = {
                    localStorage_set: true,
                    timestamp: Date.now(),
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    cookiesEnabled: navigator.cookieEnabled,
                    sessionStorage: typeof sessionStorage !== 'undefined'
                };
                
                console.log('✅ Client-side installation verification:', installVerification);
                
                // Отправляем данные о завершении установки на сервер для логирования
                fetch('install.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'install_verification',
                        verification_data: installVerification,
                        install_completed: true
                    })
                }).catch(e => console.log('Could not send verification data:', e));
                
            } catch (e) {
                console.log('Could not set localStorage:', e);
            }
        });
        });
    </script>
</body>
</html>
<?php
    // ФИНАЛЬНАЯ ПРОВЕРКА УСПЕШНОГО ЗАВЕРШЕНИЯ УСТАНОВКИ
    $finalVerification = [
        'installation_completed' => true,
        'placement_bound' => isset($bindResult) && $bindResult->isSuccess(),
        'session_flags_set' => isset($_SESSION['install_completed']) && $_SESSION['install_completed'] === true,
        'cookies_set' => isset($_COOKIE['b24_install_completed']),
        'completion_timestamp' => date('Y-m-d H:i:s'),
        'total_execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
    ];
    
    $logger->info('🎉 FINAL INSTALLATION VERIFICATION COMPLETE', $finalVerification);
    
    // Логируем успешное завершение
    $logger->info('Installation completed successfully');

} catch (\Throwable $e) {
    $errorMessage = 'Install Error: ' . $e->getMessage();
    $logger->error($errorMessage, [
        'trace' => $e->getTraceAsString(),
        'request_params' => $_REQUEST,
        'error_type' => get_class($e)
    ]);
    
    // Показываем ошибку и сразу завершаем
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Ошибка установки</title><style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;} .container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .error{background:#ffebee;padding:15px;border-radius:5px;margin:10px 0;color:#d32f2f;}</style></head><body><div class="container">';
    echo '<h1>Ошибка при установке приложения</h1>';
    echo '<div class="error">';
    echo '<p><strong>Описание ошибки:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>Рекомендации:</strong></p>';
    echo '<ul>';
    echo '<li>Проверьте стабильность интернет-соединения</li>';
    echo '<li>Убедитесь, что портал Битрикс24 доступен</li>';
    echo '<li>Попробуйте переустановить приложение через несколько минут</li>';
    echo '<li>Обратитесь к администратору если проблема повторяется</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div></body></html>';
}
?>