<?php
// auth-callback.php - ФИНАЛЬНАЯ ВЕРСИЯ

require_once __DIR__ . '/../vendor/autoload.php';

use Bitrix24\SDK\Core\ApiClient;
use Bitrix24\SDK\Core\ApiLevelErrorHandler;
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\Scope;
use Bitrix24\SDK\Infrastructure\HttpClient\RequestId\DefaultRequestIdGenerator; // ПРАВИЛЬНЫЙ КЛАСС
use Bitrix24\SDK\Services\OAuth\Service\OAuth;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\HttpClient;

$logger = new Logger('b24-auth-log');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

try {
    if (empty($_GET['code'])) {
        throw new \InvalidArgumentException('Authorization code is empty.');
    }
    $logger->info('Got authorization code.');

    // ПРАВИЛЬНЫЙ КОНСТРУКТОР ApiClient
    $apiClient = new ApiClient(new Credentials(null, null, null, null), HttpClient::create(), new DefaultRequestIdGenerator(), $logger);
    
    $core = new Core($apiClient, new ApiLevelErrorHandler($logger), new EventDispatcher(), $logger);
    $oauthService = new OAuth($core);
    $appProfile = new ApplicationProfile(
        getenv('BITRIX24_CLIENT_ID'), getenv('BITRIX24_CLIENT_SECRET'), new Scope(['crm', 'user', 'placement'])
    );
    $credentials = $oauthService->getNewAccessTokenByAuthCode($_GET['code'], $appProfile);
    
    $dataToSave = [
        'access_token'  => $credentials->getAuthToken()->accessToken, 'refresh_token' => $credentials->getAuthToken()->refreshToken,
        'expires'       => $credentials->getAuthToken()->expires, 'domain_url'    => $credentials->getDomainUrl(),
    ];
    file_put_contents(__DIR__ . '/../tokens.json', json_encode($dataToSave));
    $logger->info('Tokens saved to tokens.json.');
    header('Location: https://dimpin-app.store/apps/led-calculator-widget/public/index.php');
    exit;
} catch (\Throwable $e) {
    http_response_code(500); echo 'Auth Error: ' . $e->getMessage();
    $logger->error('Auth Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
}