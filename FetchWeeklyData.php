<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "air");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// คำสั่ง SQL เพื่อดึงข้อมูลค่าเฉลี่ยอุณหภูมิในแต่ละวันของ 7 วันที่ผ่านมา
$sql = "SELECT DATE(time) AS date, AVG(Temperature) AS avg_temp 
        FROM data_imt_copy
        WHERE time >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(time) 
        ORDER BY date DESC"; // เรียงข้อมูลจากวันที่ล่าสุด

// ดึงข้อมูลจากฐานข้อมูล
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;  // เก็บข้อมูลวันที่และค่าเฉลี่ยอุณหภูมิในแต่ละวัน
    }
} else {
    $data = ["status" => "error", "message" => "Query failed: " . $conn->error];
}

// ส่งข้อมูล JSON
echo json_encode($data);

// ปิดการเชื่อมต่อ
$conn->close();
?>
