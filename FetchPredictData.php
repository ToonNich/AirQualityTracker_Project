<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "air");

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Query to fetch the latest 6 rows, selecting only the necessary columns (ID, Time, Pm25)
$sql = "SELECT ID, Time, Pm25 FROM data_predict ORDER BY ID DESC, Time DESC LIMIT 6";
$result = $conn->query($sql);

$data = [];
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // ตัดวันที่ออกและเก็บแค่เวลา (HH:MM:SS)
            $row['Time'] = date("H:i:s", strtotime($row['Time']));  // เก็บแค่เวลา
            $data[] = $row;  // เก็บข้อมูลแต่ละแถวลงใน array
        }
    } else {
        $data = ["status" => "error", "message" => "No data available"];
    }
} else {
    $data = ["status" => "error", "message" => "Query failed: " . $conn->error];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($data);

// Close the connection
$conn->close();
?>
