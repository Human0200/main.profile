<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
error_reporting(0);

// Логируем все что приходит в ту же директорию
$debug_file = __DIR__ . "/debug.txt";

try {
    file_put_contents($debug_file, 
        date('Y-m-d H:i:s') . " - Начало обработки\n" .
        "POST data: " . print_r($_POST, true) . "\n" .
        "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", 
        FILE_APPEND
    );

    // Минимальное подключение Bitrix
    $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
    define("NO_KEEP_STATISTIC", true);
    define("NOT_CHECK_PERMISSIONS", true);
    define("BX_NO_ACCELERATOR_RESET", true);
    

    // Очищаем любой предыдущий вывод
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Проверяем авторизацию
    global $USER;
    if (!$USER || !$USER->IsAuthorized()) {
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Ошибка: пользователь не авторизован\n", FILE_APPEND);
        header('Content-Type: application/json');
        die(json_encode(array('error' => true, 'message' => 'Пользователь не авторизован')));
    }

    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Пользователь авторизован, ID: " . $USER->GetID() . "\n", FILE_APPEND);

    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Ошибка: неверный метод запроса\n", FILE_APPEND);
        header('Content-Type: application/json');
        die(json_encode(array('error' => true, 'message' => 'Неверный метод запроса')));
    }

    // Проверяем токен безопасности
    if (!check_bitrix_sessid()) {
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Ошибка: неверный токен безопасности\n", FILE_APPEND);
        header('Content-Type: application/json');
        die(json_encode(array('error' => true, 'message' => 'Неверный токен безопасности')));
    }

    // Проверяем что пришел запрос на сохранение
    if (!isset($_POST["save"]) || $_POST["save"] !== "Y") {
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Ошибка: неверный запрос save\n", FILE_APPEND);
        header('Content-Type: application/json');
        die(json_encode(array('error' => true, 'message' => 'Неверный запрос')));
    }

    $userID = $USER->GetID();
    $arFields = array();

    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Начинаем валидацию полей\n", FILE_APPEND);

    // Валидация и сбор данных
    if (isset($_POST["LOGIN"]) && trim($_POST["LOGIN"]) !== '') {
        $arFields["LOGIN"] = trim($_POST["LOGIN"]);
    }

    if (isset($_POST["EMAIL"]) && trim($_POST["EMAIL"]) !== '') {
        $arFields["EMAIL"] = trim($_POST["EMAIL"]);
    }

    // Имя, фамилия, отчество
    if (isset($_POST["NAME"])) {
        $arFields["NAME"] = trim($_POST["NAME"]);
    }
    if (isset($_POST["LAST_NAME"])) {
        $arFields["LAST_NAME"] = trim($_POST["LAST_NAME"]);
    }
    if (isset($_POST["SECOND_NAME"])) {
        $arFields["SECOND_NAME"] = trim($_POST["SECOND_NAME"]);
    }

    // Телефоны
    if (isset($_POST["PHONE_NUMBER"]) && trim($_POST["PHONE_NUMBER"]) !== '') {
        $arFields["PHONE_NUMBER"] = trim($_POST["PHONE_NUMBER"]);
    }

    if (isset($_POST["PERSONAL_PHONE"]) && trim($_POST["PERSONAL_PHONE"]) !== '') {
        $arFields["PERSONAL_PHONE"] = trim($_POST["PERSONAL_PHONE"]);
    }

    file_put_contents($debug_file, 
        date('Y-m-d H:i:s') . " - Поля для сохранения: " . print_r($arFields, true) . "\n", 
        FILE_APPEND
    );

    // Обновляем пользователя
    $user = new CUser;
    $result = $user->Update($userID, $arFields);

    file_put_contents($debug_file, 
        date('Y-m-d H:i:s') . " - Результат обновления: " . ($result ? 'успех' : 'ошибка') . "\n" .
        "Ошибка пользователя: " . $user->LAST_ERROR . "\n", 
        FILE_APPEND
    );

    // Очищаем буфер еще раз перед выводом
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    
    if ($result) {
        // Успешно сохранено
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Отправляем успешный ответ\n", FILE_APPEND);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Изменения сохранены',
            'data' => $arFields
        ));
        
    } else {
        // Ошибка сохранения
        $error_message = $user->LAST_ERROR ? $user->LAST_ERROR : 'Ошибка при сохранении данных';
        
        file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Ошибка сохранения: " . $error_message . "\n", FILE_APPEND);
        
        echo json_encode(array(
            'error' => true,
            'message' => $error_message
        ));
    }

} catch (Exception $e) {
    // Очищаем буфер при ошибке
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(array('error' => true, 'message' => 'Внутренняя ошибка сервера'));
}

die();
?>