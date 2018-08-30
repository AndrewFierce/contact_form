<?php

//Подключение класса для работы с mysql
include_once('mysql.php');

//Использование пространства имен
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Подключение класса для работы с phpmailer
include_once('PHPMailer.php');
include_once('SMTP.php');
include_once('Exception.php');

//Функция для запуска сессии
session_start();

//Сообщения об ошибках со стороны пользователя
$errorMSG = "";

// Имя клиента
if (empty($_POST["name"])) {
    $errorMSG = "Поле с именем должно быть заполнено!";
} else {
    $name = $_POST["name"];
}

// Почта клиента
if (empty($_POST["email"])) {
    $errorMSG .= "Поле с почтой должно быть заполнено!";
} else {
    $email = $_POST["email"];
}

// Сообщение
if (empty($_POST["message"])) {
    $errorMSG .= "Текст сообщения обязателен";
} else {
    $message = $_POST["message"];
}

//проверяет соответствие коду CAPTCHA
if ($_SESSION["code"] != $_POST["captcha"]) {
  //сообщаем строку true, если код соответствует
  $errorMSG .= "Код с картинке введен не верно! Попробуйте еще раз.";
}

$EmailTo = "email@gmail.com";  //На какую почту отправить сообщение от клиента
$Subject = "Требуется обратная связь от пользователя ".$name;   //Тема сообщения

// Формирование тела сообщения
$Body = "";
$Body .= "Имя: ";
$Body .= $name;
$Body .= "\n";
$Body .= "Почта: ";
$Body .= $email;
$Body .= "\n";
$Body .= "Сообщение: ";
$Body .= $message;
$Body .= "\n";

$mysql = new MySQL('localhost', 'root', '', 'mesage');  //Создаем класс для работы с БД MySQL

$mail = new PHPMailer(true);    //Создаем класс для работы с настройками SMTP
$posts = array();   //Массив для проверки данных из БД MySQL
$success = false;   //Проверка на успех отправки сообщения
$file = 'log.txt';  //Переменная файла

//Поиск совпадений сообщения и получателя по базе данных
try{
    $posts = $mysql->and_where(array('message' => $message, 'email' => $email))->get('contacts', array('email', 'message'));
}catch(Exception $e){
    //Запись ошибки в текстовый фал
    file_put_contents($file, 'Ошибка SQL запроса: '.$e->getMessage(), FILE_APPEND | LOCK_EX);
}
//Если ошибок в форме нет и подобного сообщения не было от данного адресата, то выполняется код ниже
if ($errorMSG == "") {
    if (empty($posts)) {
        //добавляем Имя, почту, сообщение и текущую дату в MySQL
        try{
            $mysql->insert('contacts', array('name' => $name, 'email' => $email, 'message' => $message, 'date' => date("Y-m-d")));
        }catch(Exception $e){
            //Запись ошибки в текстовый фал
            file_put_contents($file, 'Ошибка SQL запроса: '.$e->getMessage(), FILE_APPEND | LOCK_EX);
        }
        // Настройки SMTP
        $mail->isSMTP();    //Отправляем сообщение по SMTP
        $mail->CharSet = 'utf-8';   //выставляем нужную кодировку (в противном случае текст сообщения будет нечитаемым)
        $mail->Host = "smtp.gmail.com"; // Ваш SMTP host
        $mail->SMTPAuth = true;     //Выставляем true, если почтовый серевер требует авторизацию, в противном случае значение false
        $mail->Username = "email@gmail.com"; // Ваш логин на почтовом сервере
        $mail->Password = "password"; // Ваш пароль на почтовом сервере
        $mail->SMTPSecure = "ssl";              // Используется протокол ssl для шифрования
        $mail->Port = 465;                      // Порт SSL
        $mail->setFrom($EmailTo); // От кого осуществляется отправка сообщения
        $mail->addAddress($EmailTo); // Кому необходимо доставить сообщение
        // Письмо
        $mail->isHTML(true);    //Формат письма HTML
        $mail->Subject = $Subject; // Заголовок письма
        $mail->Body = $Body."From:".$email; // Текст письма
        
        // Отправка сообщения
        try{
            $success = $mail->send();
        } catch (Exception $e) {
            //Запись ошибки в текстовый фал
            file_put_contents($file, "SMTP ошибка: " . $mail->ErrorInfo, FILE_APPEND | LOCK_EX);
            //отправка сообщения локальным почтовым сервером
            $success = mail($EmailTo, $Subject, $Body, "From:".$email);
        }
    }
    //В случае, если есть совпадения в базе
    else {
        $errorMSG = "Пожалуйста, измените текст сообщения";
        $success = false;
    }
}

// В случае успеха отправки сообщения возвращаем значение success
if ($success){
    echo "success";
}
else{
    if($errorMSG == ""){
        //Запись ошибки в текстовый фал
        file_put_contents($file, "Ошибка функции mail()! Проверьте локальные настройки почтового сервера!", FILE_APPEND | LOCK_EX);
        echo "Ошибка при отправке сообщения. Не волнуйтесь! Ваша заявка будет обработана. Наши специалисты работают над проблемой отправки писем!";
    } else {
        echo $errorMSG;
    }
}

?>
