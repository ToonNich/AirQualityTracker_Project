<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "iot_class");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Connection failed: " . $conn->connect_error
    ]);
    exit();
}

// ดึงข้อมูล PM2_5, Temperature, Humidity 1 แถวล่าสุดจากฐานข้อมูล
$sql = "SELECT PM2_5, Temperature, Humidity FROM test1 ORDER BY ID DESC LIMIT 1";
$result = $conn->query($sql);

// ตรวจสอบผลลัพธ์
if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . $conn->error
    ]);
    $conn->close();
    exit();
}

// จัดการข้อมูลที่ดึงมา
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $data = [
        "PM2_5" => (float) $row['PM2_5'],
        "Temperature" => (float) $row['Temperature'],
        "Humidity" => (float) $row['Humidity']
    ];
} else {
    http_response_code(404);
    $data = ["status" => "error", "message" => "No data available"];
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ส่งข้อมูล JSON
echo json_encode($data);
?>
