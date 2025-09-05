<?php
declare(strict_types=1);

use Bitrix24\SDK\Services\ServiceBuilderFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Вебхук для получения данных
$webhookUrl = 'https://ledts.bitrix24.ru/rest/770/j4lqxypctvqkqsyx/';

try {
    // Создаем сервис через вебхук
    $B24 = ServiceBuilderFactory::createServiceBuilderFromWebhook($webhookUrl);
    
    // Получаем данные текущего пользователя
    $currentUser = $B24->core->call('user.current')->getResponseData()->getResult();
    
    // Получаем ID сделки только из параметров запроса
    $dealId = $_POST['deal_id'] ?? $_POST['ID'] ?? $_REQUEST['deal_id'] ?? $_REQUEST['ID'] ?? null;
    
    // Если ID сделки не найден в обычных параметрах, пробуем из PLACEMENT_OPTIONS
    if (!$dealId && !empty($_POST['PLACEMENT_OPTIONS'])) {
        $placementOptions = json_decode($_POST['PLACEMENT_OPTIONS'], true);
        if ($placementOptions && isset($placementOptions['ID'])) {
            $dealId = $placementOptions['ID'];
        }
    }
    
    // Если есть ID сделки, получаем данные сделки
    $dealData = null;
    if ($dealId) {
        $dealData = $B24->core->call('crm.deal.get', ['id' => $dealId])->getResponseData()->getResult();
    }
    
    // Создаем URL для iframe с параметрами
    $iframeUrl = "https://dimpin-app.store/apps/led-calculator/?" . http_build_query([
        'dealId' => $dealId,
        'userId' => $currentUser['ID'],
        'domain' => $_REQUEST['DOMAIN'] ?? 'ledts.bitrix24.ru',
        'memberId' => $_REQUEST['MEMBER_ID'] ?? 'current',
        'authId' => $_REQUEST['AUTH_ID'] ?? null
    ]);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LED Калькулятор</title>
    <script src="//api.bitrix24.com/api/v1/"></script>
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
        .debug-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .debug-section h3 {
            margin-top: 0;
            color: #495057;
        }
        pre {
            background: white;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
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
        <?php if ($dealId && $dealData): ?>
        <div class="info-section">
            <p><strong>Сделка:</strong> <?php echo htmlspecialchars($dealData['TITLE']); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($iframeUrl)): ?>
        <iframe id="calculator-frame" 
                class="calculator-frame"
                src="<?php echo htmlspecialchars($iframeUrl); ?>"
                allow="fullscreen"
                sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-presentation">
        </iframe>
        <?php else: ?>
        <p>Ошибка загрузки калькулятора.</p>
        <?php endif; ?>
    </div>
    
    <script>
        BX24.init(function(){
            console.log('LED Калькулятор загружен');
        });
    </script>
</body>
</html>
