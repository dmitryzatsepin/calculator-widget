<?php
$_REQUEST = array("PLACEMENT" => "DEFAULT", "DOMAIN" => "ledts.bitrix24.ru", "member_id" => "test", "AUTH_ID" => "test");
$isPlacementCall = isset($_REQUEST["PLACEMENT"]) && $_REQUEST["PLACEMENT"] === "CRM_DEAL_DETAIL_ACTIVITY";
$isInstallation = !isset($_REQUEST["PLACEMENT"]) || $_REQUEST["PLACEMENT"] === "DEFAULT";
echo "PLACEMENT: " . $_REQUEST["PLACEMENT"] . "\n";
echo "isPlacementCall: " . ($isPlacementCall ? "true" : "false") . "\n";
echo "isInstallation: " . ($isInstallation ? "true" : "false") . "\n";
?>
