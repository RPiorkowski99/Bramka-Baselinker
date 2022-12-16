<?php
include("connect.php");

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
}

$oneDay = 86400;

if($maxDate == NULL){
    $date_from = 1640995200;
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

foreach($res['orders'] as $value){
    if($date_from >= $dateTo){
        break;
    }
    if($value['order_source_id'] == "example" || $value['order_source_id'] == "example" || $value['order_source_id'] == "example" || $value['order_source_id'] == "example"){
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
    
    $sql2 = "INSERT INTO baselinker_PL (order_id, order_source, data_in_status, date_in_status_MS, payment_done, status, order_status, updated_at) 
    VALUES ('$order_id', '$order_source', '$date_in_status', '$date_in_status_MS', '$payment_done', '$payment', '$order_status', '$updated_at')";

    if ($conn2->query($sql2) === TRUE) {
        echo "New record created successfully<br>";
    } else {
        echo "Error: " . $sql2 . "<br>" . $conn2->error;
    }
    
    $conn2->close();
    }
}
?>