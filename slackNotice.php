<?php
define("__ROOT__", dirname(__FILE__));

//ライブラリの読み込み
require_once __ROOT__ . "/src/Clients/NAWSApiClient.php";

//Netatmoの接続情報
$scope = NAScopes::SCOPE_READ_STATION;
$config = array(
    "client_id" => "5c5e6d850b04dc13008d6cc7",
    "client_secret" => "3rbQHDOk28gjzb0I18b0aWrKHvUwJTW7cvKKpEwD9cK",
    "username" => "ryu.kida@valu.is",
    "password" => "1Password;"
);

//WSに接続して情報を取得しメッセージ文を作る
$client = new NAWSApiClient($config);
$data = $client->getData();
$message = "";
foreach ($data["devices"] as $device) {
    //場所・気温・湿度・C02
    $message .= sprintf(
        "%s : %s°C, %s%%, %sppm" . PHP_EOL,
        $device["station_name"],
        $device["dashboard_data"]["Temperature"],
        $device["dashboard_data"]["Humidity"],
        $device["dashboard_data"]["CO2"]
    );

    //屋外モジュールなど
    foreach ($device["modules"] as $module) {
        //モジュール名・気温・湿度
        $message .= sprintf(
            "%s : %s°C, %s%% " . PHP_EOL,
            $module["module_name"],
            $module["dashboard_data"]["Temperature"],
            $module["dashboard_data"]["Humidity"]
        );
    }

    //Slackへ通知
    sendMessageAtSlack($message);
}

//Slackで通知
function sendMessageAtSlack($message)
{
    $url = "https://hooks.slack.com/services/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";
    $json = array(
        "channel" => "#3-notify-netatmo",
        "username" => "netatmo",
        "text" => $message,
        "icon_emoji" => ":four_leaf_clover:"
    );
    //POST値は、payload=json型式のパラメータ
    $data = http_build_query(array("payload" => json_encode($json)), "", "&");

    $header = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: " . strlen($data)
    );

    $options = array(
        "http" => array(
            "method" => "POST",
            "header" => implode("\r\n", $header),
            "content" => $data,
        )
    );
    //SlackへPost送信
    file_get_contents($url, false, stream_context_create($options));
}