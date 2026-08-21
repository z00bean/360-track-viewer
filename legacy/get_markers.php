<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$postData = file_get_contents("php://input");
$request = json_decode($postData, true);

$dataset_name = $request['dataset_name'];


$datasetPath = "./datasets/" . $dataset_name . "/markers.txt";

$markers = [];

$handle = fopen($datasetPath, "r");
$headers = fgetcsv($handle, 1000, ",");

while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $markers[] = array_combine($headers, $row);
}

fclose($handle);

echo json_encode($markers);
