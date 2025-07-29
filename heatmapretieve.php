<?php
// Define the directory path where the files are located
$directory = 'C:/xampp/htdocs/heatmapV1/';  // Correct path format

// Check if the directory exists
if (!is_dir($directory)) {
    die('Directory not found: ' . $directory);
}

// Scan the directory and get an array of files
$files = scandir($directory, SCANDIR_SORT_DESCENDING);

// Filter out the current (.) and parent (..) directories
$files = array_diff($files, array('.', '..'));

// Sort the files by last modification time
usort($files, function($a, $b) use ($directory) {
    $fileA = $directory . $a;
    $fileB = $directory . $b;

    // Check if the files exist and get the last modified time
    if (file_exists($fileA) && file_exists($fileB)) {
        return filemtime($fileB) - filemtime($fileA);
    }
    return 0;  // If files don't exist, don't change the order
});

// Get the latest file (first item after sorting)
$latestFile = $files[0];

// Get the file path
$latestFilePath = $directory . $latestFile;

// Check if the file exists
if (file_exists($latestFilePath)) {
    // Read the file and encode it to base64
    $imageData = base64_encode(file_get_contents($latestFilePath));

    // Return the image data as a JSON response
    echo json_encode(['imageBase64' => 'data:image/png;base64,' . $imageData]);
} else {
    echo json_encode(['error' => 'No image found']);
}
?>
