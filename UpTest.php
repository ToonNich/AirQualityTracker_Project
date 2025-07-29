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

// ดึงค่าเวลาปัจจุบันเพื่อใช้ตรวจสอบว่าเป็นนาทีที่ 0 หรือหาร 5 ลงตัว
$minute = date("i", strtotime($date)); // นาทีจากเวลา

header('Content-Type: application/json');

// ตรวจสอบข้อมูลที่รับมาเป็นตัวเลขหรือไม่
if (!is_numeric($Temp) || !is_numeric($Humi) || !is_numeric($Pm)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Temperature, Humidity, and PM2_5 must be numeric"]);
    exit();
}

// เริ่มทำการบันทึกข้อมูลลงในฐานข้อมูล

// ตรวจสอบว่าเป็นนาทีที่ 0 หรือไม่
if ($minute == 0) {
    // บันทึกลงตาราง data_imt_copy เมื่อ นาที = 0
    $stmt1 = $conn->prepare(
        "INSERT INTO data_imt_copy (Temperature, Humidity, PM2_5, time) 
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         Temperature = VALUES(Temperature), 
         Humidity = VALUES(Humidity), 
         PM2_5 = VALUES(PM2_5)"
    );
    
    if ($stmt1) {
        $stmt1->bind_param("ssss", $Temp, $Humi, $Pm, $date);
        if (!$stmt1->execute()) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to insert or update record in data_imt_copy: " . $stmt1->error]);
            exit();
        }
        $stmt1->close();
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to prepare query for data_imt_copy"]);
        exit();
    }
}


// กำหนดค่า `$minute`
$minute = date("i");

// ตรวจสอบค่าที่ได้รับมาก่อนบันทึก
if (!empty($Temp) && !empty($Humi) && !empty($Pm) && is_numeric($Temp) && is_numeric($Humi) && is_numeric($Pm)) {
    // บันทึกลงตาราง test1 ทุกๆ 1 นาที
    $stmt2 = $conn->prepare(
        "INSERT INTO test1 (Temperature, Humidity, PM2_5) 
         VALUES (?, ?, ?)"
    );

    if ($stmt2) {
        $stmt2->bind_param("sss", $Temp, $Humi, $Pm);
        if (!$stmt2->execute()) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to insert record into test1: " . $stmt2->error]);
            exit();
        }
        $stmt2->close();
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to prepare query for test1"]);
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid data received"]);
    exit();
}

$conn->close();
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Record inserted or updated successfully"]);
?>
