<?php
header('Content-Type: application/json');

// Логируем все что приходит
$debug_file = __DIR__ . "/debug.txt";
file_put_contents($debug_file, 
    date('Y-m-d H:i:s') . " - SIMPLE HANDLER\n" .
    "POST data: " . print_r($_POST, true) . "\n" .
    "SESSION: " . print_r($_SESSION, true) . "\n\n", 
    FILE_APPEND
);

// Стартуем сессию чтобы получить данные пользователя
session_start();

// Проверяем что пришли данные
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST["save"]) || $_POST["save"] !== "Y") {
    echo json_encode(array('error' => true, 'message' => 'Неверный запрос'));
    exit;
}

// Получаем данные из формы
$fields = array();
if (isset($_POST["LOGIN"])) $fields["LOGIN"] = trim($_POST["LOGIN"]);
if (isset($_POST["EMAIL"])) $fields["EMAIL"] = trim($_POST["EMAIL"]);
if (isset($_POST["NAME"])) $fields["NAME"] = trim($_POST["NAME"]);
if (isset($_POST["LAST_NAME"])) $fields["LAST_NAME"] = trim($_POST["LAST_NAME"]);
if (isset($_POST["SECOND_NAME"])) $fields["SECOND_NAME"] = trim($_POST["SECOND_NAME"]);
if (isset($_POST["PHONE_NUMBER"])) $fields["PHONE_NUMBER"] = trim($_POST["PHONE_NUMBER"]);
if (isset($_POST["PERSONAL_PHONE"])) $fields["PERSONAL_PHONE"] = trim($_POST["PERSONAL_PHONE"]);

file_put_contents($debug_file, 
    date('Y-m-d H:i:s') . " - Собранные поля: " . print_r($fields, true) . "\n", 
    FILE_APPEND
);

// Возвращаем успешный ответ (пока без реального сохранения)
echo json_encode(array(
    'success' => true,
    'message' => 'Данные получены (тест)',
    'data' => $fields
));
?>