<?php
// placement-info.php - информация о placement

echo "<h2>Информация о Placement CRM_DEAL_DETAIL_ACTIVITY</h2>";

echo "<h3>Где должна появляться кнопка:</h3>";
echo "<p>Кнопка 'LED Калькулятор' должна появляться в <strong>карточке сделки</strong> в разделе <strong>Активности</strong>.</p>";

echo "<h3>Как найти кнопку:</h3>";
echo "<ol>";
echo "<li>Откройте любую сделку в Битрикс24</li>";
echo "<li>Перейдите на вкладку 'Активности' (или 'Таймлайн')</li>";
echo "<li>В меню активности должна быть кнопка 'LED Калькулятор'</li>";
echo "<li>Кнопка может быть в выпадающем меню (три точки)</li>";
echo "</ol>";

echo "<h3>Возможные причины отсутствия кнопки:</h3>";
echo "<ul>";
echo "<li><strong>Недостаточно прав:</strong> Приложение должно иметь права crm, user, placement</li>";
echo "<li><strong>Placement не создан:</strong> Проверьте логи установки</li>";
echo "<li><strong>Кнопка скрыта:</strong> Проверьте настройки интерфейса</li>";
echo "<li><strong>Неправильный placement:</strong> Должен быть CRM_DEAL_DETAIL_ACTIVITY</li>";
echo "</ul>";

echo "<h3>Проверка:</h3>";
echo "<p><a href='simple-test.php'>Простой тест</a></p>";
echo "<p><a href='check-placements.php'>Проверить placements</a></p>";
echo "<p><a href='logs/app.log' target='_blank'>Посмотреть логи</a></p>";

echo "<h3>Альтернативные места размещения:</h3>";
echo "<p>Если кнопка не появляется в активностях, можно попробовать другие placements:</p>";
echo "<ul>";
echo "<li><strong>CRM_DEAL_DETAIL_TAB:</strong> Отдельная вкладка в карточке сделки</li>";
echo "<li><strong>CRM_DEAL_DETAIL_TOOLBAR:</strong> Кнопка в верхней панели карточки сделки</li>";
echo "<li><strong>CRM_DEAL_LIST_MENU:</strong> Кнопка в списке сделок</li>";
echo "</ul>";

echo "<h3>Тест альтернативного placement:</h3>";
echo "<p><a href='index.php?DOMAIN=ledts.bitrix24.ru&AUTH_ID=275f9668007b0afe006cd3c800000302201c07b89bf0aad4e3f7d469824a3a0ec9658d&member_id=2ed3b3005a7c2a8f82e51326f960a106&PLACEMENT=CRM_DEAL_DETAIL_TOOLBAR&PLACEMENT_OPTIONS={\"ID\":\"123\"}' target='_blank'>Тест CRM_DEAL_DETAIL_TOOLBAR</a></p>";
?> 