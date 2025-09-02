<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "iot_class");

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Function to calculate Euclidean distance between two points
function euclideanDistance($point1, $point2) {
    $sum = 0;
    foreach ($point1 as $key => $value) {
        $sum += pow($value - $point2[$key], 2);
    }
    return sqrt($sum);
}

// Function to find neighbors of a point
function regionQuery($data, $point, $epsilon) {
    $neighbors = [];
    foreach ($data as $index => $p) {
        if (euclideanDistance($point, $p) <= $epsilon) {
            $neighbors[] = $index;
        }
    }
    return $neighbors;
}

// DBSCAN algorithm
function dbscan($data, $epsilon, $minPts) {
    $clusterId = 0;  // Start with cluster ID of 0
    $n = count($data);
    $visited = array_fill(0, $n, false);
    $labels = array_fill(0, $n, -1); // All points are initially noise
    $clusters = [];

    for ($i = 0; $i < $n; $i++) {
        if ($visited[$i]) {
            continue;
        }

        $visited[$i] = true;
        $neighbors = regionQuery($data, $data[$i], $epsilon);

        if (count($neighbors) < $minPts) {
            continue; // Keep it as noise (-1)
        }

        // Create a new cluster
        $clusters[$clusterId] = [];
        $queue = $neighbors;

        while (!empty($queue)) {
            $neighbor = array_shift($queue);

            if (!$visited[$neighbor]) {
                $visited[$neighbor] = true;
                $newNeighbors = regionQuery($data, $data[$neighbor], $epsilon);
                if (count($newNeighbors) >= $minPts) {
                    $queue = array_merge($queue, array_diff($newNeighbors, $queue));
                }
            }

            if ($labels[$neighbor] == -1) { // Only add if it's not already assigned
                $labels[$neighbor] = $clusterId;
                $clusters[$clusterId][] = $data[$neighbor];
            }
        }

        $clusterId++; // Move to the next cluster
    }

    return ['clusters' => $clusters, 'labels' => $labels];
}

// K-means algorithm to divide the data into exactly 5 clusters
function kMeans($data, $k, $maxIterations = 100) {
    $centroids = [];
    $n = count($data);
    
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

// Query to fetch temperature and humidity data from the database
$sql = "SELECT PM2_5, Humidity, Temperature FROM air_quality_tracker WHERE Humidity > 0 ORDER BY RAND() LIMIT 3000";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [$row['Temperature'], $row['Humidity'], $row['PM2_5']];
    }
} else {
    echo json_encode(["status" => "error", "message" => "No data available"]);
    exit();
}

// Close the database connection
$conn->close();

// Parameters for DBSCAN
$epsilon = 1.5;
$minPts = 3;

// Run DBSCAN
$dbscanResult = dbscan($data, $epsilon, $minPts);
$clusters = $dbscanResult['clusters'];

// Use K-means to further divide the data into exactly 5 clusters
$k = 5;
$kMeansClusters = kMeans($data, $k);

// Output the results as JSON
echo json_encode(["clusters" => $kMeansClusters]);
?>

