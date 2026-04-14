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

// Подключаем минимальное ядро
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

$errorMessage = "";
// Запрашиваем данные у серверов Битрикс
$arUpdates = CUpdateClient::GetUpdatesList($errorMessage, "ru", "Y");

if ($errorMessage) {
    echo "Ошибка получения обновлений: " . $errorMessage . "\n";
} elseif (is_array($arUpdates)) {
    
    // Проверка на необходимость обновления системы SiteUpdate
    $siteUpdateRequired = false;
    if (isset($arUpdates["UPDATE_SYSTEM"])) {
        // Защита от разного формата парсинга XML Битриксом
        if (is_scalar($arUpdates["UPDATE_SYSTEM"]) && $arUpdates["UPDATE_SYSTEM"] == "Y") {
            $siteUpdateRequired = true;
        } elseif (is_array($arUpdates["UPDATE_SYSTEM"]) && isset($arUpdates["UPDATE_SYSTEM"][0]["#"]) && $arUpdates["UPDATE_SYSTEM"][0]["#"] == "Y") {
            $siteUpdateRequired = true;
        }
    }

    if ($siteUpdateRequired) {
        echo "[!] ВНИМАНИЕ: Выпущено обновление системы SiteUpdate! Его необходимо установить до модулей.\n";
    }

    // Подсчет модулей
    $modules = 0;
    if (isset($arUpdates["MODULES"][0]["#"]["MODULE"])) {
        $modules = count($arUpdates["MODULES"][0]["#"]["MODULE"]);
    }
    
    // Подсчет языков
    $langs = 0;
    if (isset($arUpdates["LANGS"][0]["#"]["INST"])) {
        $langs = count($arUpdates["LANGS"][0]["#"]["INST"]);
    }

    // Вывод результатов
    echo "Доступно обновлений модулей: " . $modules . "\n";
    echo "Доступно обновлений языков: " . $langs . "\n";
    
    // Проверка срока действия лицензии
    if (isset($arUpdates["CLIENT"][0]["@"]["DATE_TO"])) {
        echo "Лицензия активна до: " . $arUpdates["CLIENT"][0]["@"]["DATE_TO"] . "\n";
    }

} else {
    echo "Сервер обновлений Битрикс вернул пустой ответ.\n";
}
?>