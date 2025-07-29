<?php
date_default_timezone_set("Asia/Bangkok");
require "C:/xampp/htdocs/AirQualityTracker/DataBaseConnection.php"; // เปลี่ยนที่อยู่ให้ถูกต้อง

// ดึงข้อมูล JSON ที่ส่งมา
$data = json_decode(file_get_contents("php://input"), true);

// ตรวจสอบว่าได้รับข้อมูลครบหรือไม่
if (!$data || !isset($data["Temperature"]) || !isset($data["Humidity"]) || !isset($data["PM2_5"])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
    exit();
}

// รับค่าจาก JSON
$Temp = trim($data["Temperature"]);
$Humi = trim($data["Humidity"]);
$Pm = trim($data["PM2_5"]);
$know = date("Y-m-d H:i:s"); // เวลาในขณะนั้น
$date = isset($data["time"]) ? trim($data["time"]) : $know; // ถ้ามีค่าจาก JSON จะใช้เวลานั้น ถ้าไม่มีจะใช้เวลาปัจจุบัน

header('Content-Type: application/json');

// ตรวจสอบข้อมูลที่รับมาเป็นตัวเลขหรือไม่
if (!is_numeric($Temp) || !is_numeric($Humi) || !is_numeric($Pm)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Temperature, Humidity, and PM2_5 must be numeric"]);
    exit();
}

// ใช้ prepared statement เพื่อเพิ่มความปลอดภัย
$stmt = $conn->prepare(
    "INSERT INTO data_imt (Temperature, Humidity, PM2_5, time) 
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE 
     Temperature = VALUES(Temperature), 
     Humidity = VALUES(Humidity), 
     PM2_5 = VALUES(PM2_5)"
);

if ($stmt) {
    $stmt->bind_param("ssss", $Temp, $Humi, $Pm, $date); // สั่งให้ค่านี้ไปที่ database
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Record inserted or updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to insert or update record: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to prepare query"]);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
