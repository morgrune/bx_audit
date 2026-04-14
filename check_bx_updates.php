<?php
// Запрашиваем путь к сайту как аргумент командной строки
if (empty($argv[1])) {
    die("Использование: php check_bx_updates.php /путь/к/корню/сайта\n");
}
$_SERVER["DOCUMENT_ROOT"] = rtrim($argv[1], '/');

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("BX_NO_ACCELERATOR_RESET", true);
define("CHK_EVENT", true);

// Подключаем ядро
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

$errorMessage = "";
$arUpdates = CUpdateClient::GetUpdatesList($errorMessage, "ru", "Y");

if ($errorMessage) {
    echo "Ошибка получения обновлений: " . $errorMessage . "\n";
} else {
    $modules = isset($arUpdates["MODULES"][0]["#"]["MODULE"]) ? count($arUpdates["MODULES"][0]["#"]["MODULE"]) : 0;
    $langs = isset($arUpdates["LANGS"][0]["#"]["INST"]) ? count($arUpdates["LANGS"][0]["#"]["INST"]) : 0;
    
    echo "Доступно обновлений модулей: " . $modules . "\n";
    echo "Доступно обновлений языков: " . $langs . "\n";
}
?>