<?php
define("API_KEY", "699140372:AAEWyyeB_Tze1jXkuSv7Lsqqlcvm5JfCyNU");
define("BASE_URL", "https://api.telegram.org/bot");

require "dbHandler.php";


function sendMessage($method, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BASE_URL . API_KEY . "/" . $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if (curl_error($ch)) {
        echo curl_error($ch);
    } else {
        return json_decode($result);
    }
}


$response = file_get_contents("php://input");
$response = json_decode($response, true);
$user_id = $response["message"]["from"]["id"];
$chat_id = $response["message"]["chat"]["id"];
$first_name = $response["message"]["chat"]["first_name"];
$last_name = $response["message"]["chat"]["last_name"];
$text = $response["message"]["text"];



try {
    $sql = "SELECT * FROM history WHERE user_id = $user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $sql . "<br>" . $e->getMessage();
}


if ($result[0]["step"] == 0 || empty($result)) {

    try {
        $sql = "INSERT INTO history (user_id, step) VALUES ($user_id, 1)";
        $conn->exec($sql);
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }
    $msg = " سلام آقای  " . $first_name . $last_name . " به ربات خودتون خوش آمدید. ";
    $msg .= "\n\n  لطفا عدد اول را وارد کنید:";
    $data = array(
        "chat_id" => $chat_id,
        "text" => $msg,
    );
    sendMessage("sendMessage", $data);

} elseif ($result[0]["step"] == 1) {

    try {
        $sql = "UPDATE history SET first_number = $text, step = 2 WHERE user_id=$user_id";
        $conn->exec($sql);
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }
    $msg = "لطفا عدد دوم را وارد کنید";
    $data = array(
        "chat_id" => $chat_id,
        "text" => $msg,
    );
    sendMessage("sendMessage", $data);

} elseif ($result[0]["step"] == 2) {

    try {
        $sql = "UPDATE history SET second_number = $text, step = 3 WHERE user_id = $user_id";
        $conn->exec($sql);
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }
    $msg = "لطفا یکی از عملیات های  ( + ، - ، * ، / ، % ) را مشخص کنید ";
    $data = array(
        "chat_id" => $chat_id,
        "text" => $msg,
    );
    sendMessage("sendMessage", $data);

} elseif ($result[0]["step"] == 3) {

    try {
        $sql = "UPDATE history SET step = 1 WHERE user_id = $user_id";
        $conn->exec($sql);
    } catch (PDOException $e) {
        echo $sql . "<br>" . $e->getMessage();
    }

    $first_number = $result[0]["first_number"];
    $second_number = $result[0]["second_number"];

    switch ($text) {
        case "+":
            $answer = $first_number + $second_number;
            break;
        case "-":
            $answer = $first_number - $second_number;
            break;
        case "*":
            $answer = $first_number * $second_number;
            break;
        case "/":
            $answer = $first_number / $second_number;
            break;
        case "%":
            $answer = $first_number % $second_number;
            break;
        default:
            $answer = "لطفا عمگر را درست انتخاب کنید";
            break;
    }
    $msg = $first_number . " " . $text . " " . $second_number . " = " . $answer;
    $msg .= "\n لطفا عدد اول را وارد کنید:";
    $data = array(
        "chat_id" => $chat_id,
        "text" => $msg,
    );
    sendMessage("sendMessage", $data);
}


?>

