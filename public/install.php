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
        ?>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <script>
            BX24.init(function(){
                BX24.installFinish();
            });
        </script>
        <?php
    } else {
        print ('Failed to bind placement.');
    }
} catch (Throwable $e) {
    print ('Error: ' . $e->getMessage());
}