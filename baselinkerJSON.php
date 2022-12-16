<?php
include("connect.php");

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
    $sql = "SELECT * FROM baselinker_PL";
    $array = [];
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if($row["payment_done"] != 0 || ($row["payment_done"] == 0 && $row["status"] == "Wybierz w przypadku płatności za przesyłkę za pobraniem")){
            $dt = new DateTime($row["data_in_status"]);
            $temparray = [];
            $temparray['ID'] = $row["ID"];
            $temparray['order_id'] = $row["order_id"];
            if($row["order_source"] == "EXAMPLE"){
                $temparray['order_source'] = "EXAMPLE";
            } else if ($row["order_source"] == "EXAMPLE"){
                $temparray['order_source'] = "EXAMPLE";
            } else if ($row["order_source"] == "EXAMPLE"){
                $temparray['order_source'] = "EXAMPLE";
            } else if ($row["order_source"] == "EXAMPLE"){
                $temparray['order_source'] = "EXAMPLE";
            } else if ($row["order_source"] == "EXAMPLE"){
                $temparray['order_source'] = "EXAMPLE";
            } else {
                $temparray['order_source'] = $row["order_source"];
            }
            $temparray['data_in_status'] = $dt->format('Y-m-d');
            $temparray['data_in_status_monthly'] = $dt->format('Y-m');
            $temparray['data_in_status_hours'] = $row["data_in_status"];
            $temparray['date_in_status_MS'] = $row["date_in_status_MS"];
            $temparray['payment_done'] = $row["payment_done"] - ($row["payment_done"] * 0.23)/(1.23);
            $temparray['status'] = $row["status"];

            array_push($array, $temparray);
            }
        }
    } else {
        echo "<h2>Brak danych</h2>";
    }
}

$conn->close();
echo json_encode($array);
?>
