<?php
require("connect.php");

$conn = mysqli_connect($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    
    $sql = "SELECT MAX(date_in_status_MS) as Max_Date FROM baselinker_PL";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $maxDate = $row["Max_Date"];
        }
    } else {
        echo "<h2>Brak danych</h2>";
    }
    $sql2 = "SELECT UpdatedTime FROM CheckData";
    $result2 = $conn->query($sql2);
    if ($result2->num_rows > 0) {
        while($row2 = $result2->fetch_assoc()) {
            $updatedTime = $row2["UpdatedTime"];
        }
    } else {
        echo "<h2>Brak danych</h2>";
    }
}

$oneDay = 86400;

if($maxDate == NULL){
    $date_from = 1640995200;
    $dateTo = $date_from + $oneDay;
} else if ($date_from <= $maxDate){
    $date_from = $updatedTime;
    $dateTo = $date_from + $oneDay;

} else {
    $date_from = $maxDate;
    $dateTo = $maxDate + $oneDay;
}
$methodParams = '{
    "date_confirmed_from":' . $date_from . ',
    "get_unconfirmed_orders": false
}';
$apiParams = [
    "method" => "getOrders",
    "parameters" => $methodParams
];

$curl = curl_init("https://api.baselinker.com/connector.php");
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_HTTPHEADER, ["X-BLToken: API-KEY"]);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($apiParams));
curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
$res=json_decode(curl_exec($curl),true);

$i = 0;

mysqli_close($conn);
echo "Najwyższa data z SQL: " . $maxDate . "<br>";
echo "Data pobrana z baselinkera: " . $date_from . "<br>";
echo "Okres " . $dateTo;

$dane = @file_get_contents('https://api.nbp.pl/api/exchangerates/rates/a/eur/today/?format=json');
$json = json_decode($dane,TRUE);
$mid = $json["rates"][0]["mid"];
if ($mid == null){
    $dane = @file_get_contents('https://api.nbp.pl/api/exchangerates/rates/a/eur/last/?format=json');
    $json = json_decode($dane,TRUE);
    $mid = $json["rates"][0]["mid"];
}

$daneGBP = @file_get_contents('https://api.nbp.pl/api/exchangerates/rates/a/gbp/today/?format=json');
$jsonGBP = json_decode($dane,TRUE);
$midGBP = $jsonGBP["rates"][0]["mid"];
if ($midGBP == null){
    $daneGBP = @file_get_contents('https://api.nbp.pl/api/exchangerates/rates/a/gbp/last/?format=json');
    $jsonGBP = json_decode($daneGBP,TRUE);
    $midGBP = $jsonGBP["rates"][0]["mid"];
}

foreach($res['orders'] as $key => $value){
    if($date_from >= $dateTo){
        break;
    }
    if($value['order_source_id'] == "3013347" || $value['order_source_id'] == "14063" || $value['order_source_id'] == "14065" || $value['order_source_id'] == "22674"){
    $i = $i + 1;
    $date_in_status = date("Y-m-d H:i:s", $value['date_add']);
    $date_in_status_MS = $value['date_add'];
    $order_id = $value['order_id'];
    $order_source = $value['order_source_id'].$value['order_source'];
    $payment = $value['payment_method'];
    $order_status = $value['order_status_id'];
    $valuesOfProd = count($value['products']) . " ";
    for($i=0; $i < $valuesOfProd; $i++){
        if($value['products'][$i]['name'] == "Rabat"){
            $valueOfOrderRabat = $value['products'][$i]['price_brutto'];
        }
        $valueOfOrder += ($value['products'][$i]['price_brutto'] * $value['products'][$i]['quantity']). "<br>" ;
    }

    $valueOfOrderWithDelivery = $valueOfOrder + $value['delivery_price'] ."<br>";
    if($value['payment_method'] == "Płatność przy odbiorze" || $value['payment_method'] == "Cash on delivery (COD)"){
        $value['payment_done'] = $valueOfOrderWithDelivery;
    }
    if($value['currency'] == 'PLN'){
        $payment_done = $value['payment_done'];
    } else if ($value['currency'] == 'EUR'){
        $payment_done = $value['payment_done'] * $mid;
    } else if ($value['currency'] == 'GBP'){
        $payment_done = $value['payment_done'] * $midBGP;
    }
    $updated_at = time();
    $valueOfOrder = 0;
    $conn2 = new mysqli($servername, $username, $password, $dbname);

    if ($conn2->connect_error) {
        die("Connection failed: " . $conn2->connect_error);
    }

    echo "DUPLIKACJA REKORDU: " . $order_id . " CZAS: ";
    $sql3 = "UPDATE baselinker_PL SET order_status = '$order_status', payment_done = '$payment_done', updated_at = '$updated_at' WHERE order_id = $order_id";
    $conn2->query($sql3);
    $sql4 = "SELECT MAX(date_in_status_MS) as Max_data_in_status FROM baselinker_PL WHERE updated_at = (SELECT MAX(updated_at) as Max_updated_date FROM baselinker_PL)";
    $result3 = $conn2->query($sql4);
    while($row3 = $result3->fetch_assoc()) {
        $maxUpdated = $row3["Max_data_in_status"];
    }
    if($maxUpdated == $maxDate){
        $maxUpdated = 1640995200;
    }
    echo $maxUpdated . " ";
    $sql5 = "UPDATE CheckData SET UpdatedTime = $maxUpdated";
    $conn2->query($sql5);
    if ($conn2->query($sql3) === TRUE) {
        echo "Record updated successfully<br>";
    } else {
        echo "Error updating record: " . $conn2->error;
    }
    $conn2->close();
    }
}   
?>