<?php

declare(strict_types=1);

use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\NullLogger;
use Throwable;

require_once __DIR__ . '/../vendor/autoload.php';

$appProfile = ApplicationProfile::initFromArray([
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => $_REQUEST['AUTH_ID'] ?? '',
    'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => $_REQUEST['REFRESH_ID'] ?? '',
    'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm,user,placement'
]);

$serviceBuilder = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
    Request::createFromGlobals(), 
    $appProfile,
    new EventDispatcher(),
    new NullLogger()
);

try {
    $placementCode = 'CRM_DEAL_DETAIL_ACTIVITY';
    $handlerUrl = 'https://dimpin-app.store/apps/led-calculator-widget/public/index.php';
    $lang = ['ru' => ['TITLE' => 'LED Калькулятор']];

    $result = $serviceBuilder
        ->getPlacementScope()
        ->placement()
        ->unbind($placementCode, null);

    $deletedCount = $result->getDeletedPlacementHandlersCount();

    $result = $serviceBuilder
        ->getPlacementScope()
        ->placement()
        ->bind($placementCode, $handlerUrl, $lang);

    if ($result->isSuccess()) {
        // Красивая страница успешной установки
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>LED Калькулятор - Установка завершена</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .success-card {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    padding: 48px;
                    text-align: center;
                    max-width: 480px;
                    width: 90%;
                    animation: slideIn 0.6s ease-out;
                }
                @keyframes slideIn {
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
                    background: #10b981;
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
                    color: #1f2937;
                    margin: 0 0 16px;
                    font-size: 28px;
                    font-weight: 600;
                }
                .message {
                    color: #6b7280;
                    margin: 0 0 32px;
                    font-size: 16px;
                    line-height: 1.6;
                }
                .btn {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 16px 32px;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
                }
                .stats {
                    background: #f8fafc;
                    border-radius: 8px;
                    padding: 16px;
                    margin: 24px 0;
                    font-size: 14px;
                    color: #64748b;
                }
            </style>
        </head>
        <body>
            <div class="success-card">
                <div class="success-icon"></div>
                <h1>Установка завершена!</h1>
                <p class="message">
                    Приложение "LED Калькулятор" успешно установлено в CRM Битрикс24.<br>
                    Теперь вы можете использовать калькулятор в сделках.
                </p>
                <div class="stats">
                    Приложение готово к использованию
                </div>
                <a href="https://ledts.bitrix24.ru/crm/deal/list/" class="btn" target="_blank">
                    Перейти в сделки
                </a>
            </div>
        </body>
        </html>
        <?php
    } else {
        print('Failed to bind placement.');
    }
} catch (Throwable $e) {
    print('Error: ' . $e->getMessage());
}