<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "air");

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Function to calculate Euclidean distance
function euclideanDistance($point1, $point2) {
    return sqrt(array_sum(array_map(fn($a, $b) => pow($a - $b, 2), $point1, $point2)));
}

// K-means algorithm
function kMeans($data, $k, $maxIterations = 100) {
    $centroids = [];
    $n = count($data);

    if ($n == 0) {
        return ["error" => "No data for clustering"];
    }

    // Randomly initialize centroids
    $indices = array_rand($data, $k);
    foreach ($indices as $i) {
        $centroids[] = array_values($data[$i]);
    }

    for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
        $clusters = array_fill(0, $k, []);

        // Assign each point to the nearest centroid
        foreach ($data as $point) {
            $distances = array_map(fn($centroid) => euclideanDistance($point, $centroid), $centroids);
            $clusterId = array_search(min($distances), $distances);
            $clusters[$clusterId][] = $point;
        }

        // Recalculate centroids
        foreach ($clusters as $clusterId => $cluster) {
            if (count($cluster) > 0) {
                $centroids[$clusterId] = array_map(
                    fn($index) => array_sum(array_column($cluster, $index)) / count($cluster),
                    array_keys($cluster[0])
                );
            }
        }
    }

    return $clusters;
}

// Query to fetch random data
$sql = "SELECT PM2_5, Humidity, Temperature FROM data_imt WHERE Humidity > 0 ORDER BY RAND() LIMIT 3000";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [(float)$row['Temperature'], (float)$row['Humidity'], (float)$row['PM2_5']];
    }
} else {
    echo json_encode(["status" => "error", "message" => "No data available"]);
    exit();
}

// Close the database connection
$conn->close();

// Run K-means
$k = 5;
$clusters = kMeans($data, $k);

// Output JSON
echo json_encode(["clusters" => $clusters], JSON_PRETTY_PRINT);
?>
