<?php

declare(strict_types=1);

use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\NullLogger;
use Throwable;

require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем конфигурацию
$config = require __DIR__ . '/config.php';

// Проверяем, установлено ли приложение
if (!$config['installed'] || empty($config['client_id']) || empty($config['client_secret'])) {
    die('Приложение не установлено. Выполните установку через install.php');
}

try {
    // Создаем профиль приложения из сохраненной конфигурации
    $appProfile = ApplicationProfile::initFromArray([
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => $config['client_id'],
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => $config['client_secret'],
        'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm,user,placement'
    ]);

    $serviceBuilder = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
        Request::createFromGlobals(),
        $appProfile,
        new EventDispatcher(),
        new NullLogger()
    );

    // Здесь ваш основной код приложения
    // Например, получение данных о сделке, контакте и т.д.
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>LED Калькулятор</title>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .calculator { max-width: 600px; margin: 0 auto; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="calculator">
            <h1>LED Калькулятор</h1>
            <p>Рассчитайте стоимость LED освещения для вашего проекта</p>
            
            <div class="form-group">
                <label for="area">Площадь помещения (м²):</label>
                <input type="number" id="area" min="1" step="0.1">
            </div>
            
            <div class="form-group">
                <label for="brightness">Требуемая освещенность (люкс):</label>
                <input type="number" id="brightness" min="100" step="10" value="300">
            </div>
            
            <div class="form-group">
                <label for="efficiency">Эффективность светильников (лм/Вт):</label>
                <input type="number" id="efficiency" min="50" step="1" value="120">
            </div>
            
            <button onclick="calculate()">Рассчитать</button>
            
            <div id="result" class="result" style="display: none;"></div>
        </div>

        <script>
            BX24.init(function(){
                console.log('LED Калькулятор загружен');
            });

            function calculate() {
                const area = parseFloat(document.getElementById('area').value);
                const brightness = parseFloat(document.getElementById('brightness').value);
                const efficiency = parseFloat(document.getElementById('efficiency').value);
                
                if (!area || !brightness || !efficiency) {
                    alert('Пожалуйста, заполните все поля');
                    return;
                }
                
                // Расчет мощности
                const totalLumens = area * brightness;
                const totalPower = totalLumens / efficiency;
                const fixtureCount = Math.ceil(area / 10); // Примерно 1 светильник на 10 м²
                const powerPerFixture = totalPower / fixtureCount;
                
                // Расчет стоимости (примерные цены)
                const fixturePrice = 1500; // руб за светильник
                const installationPrice = 500; // руб за монтаж
                const totalCost = fixtureCount * (fixturePrice + installationPrice);
                
                const result = document.getElementById('result');
                result.innerHTML = `
                    <h3>Результаты расчета:</h3>
                    <p><strong>Общая мощность:</strong> ${totalPower.toFixed(1)} Вт</p>
                    <p><strong>Количество светильников:</strong> ${fixtureCount} шт</p>
                    <p><strong>Мощность на светильник:</strong> ${powerPerFixture.toFixed(1)} Вт</p>
                    <p><strong>Примерная стоимость:</strong> ${totalCost.toLocaleString()} руб</p>
                `;
                result.style.display = 'block';
            }
        </script>
    </body>
    </html>
    <?php

} catch (Throwable $e) {
    die('Ошибка приложения: ' . $e->getMessage());
}