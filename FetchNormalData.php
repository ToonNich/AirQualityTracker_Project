<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "air");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// ดึงข้อมูลเฉพาะของวันปัจจุบัน ตั้งแต่ 01:00 น. เป็นต้นไป
$sql = "SELECT time, PM2_5, Humidity, Temperature 
        FROM data_imt_copy 
        WHERE DATE(time) = CURDATE() AND HOUR(time) >= 1
        ORDER BY time ASC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    $data = ["status" => "error", "message" => "Query failed: " . $conn->error];
}

// ส่งข้อมูล JSON
echo json_encode($data);

// ปิดการเชื่อมต่อ
$conn->close();
?>
