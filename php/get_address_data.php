<?php
header('Content-Type: application/json');

$address_data = json_decode(file_get_contents('address.json'), true);

if (!$address_data) {
    echo json_encode(['error' => 'Address data not found or invalid.']);
    exit;
}

$region = $_GET['region'] ?? null;
$province = $_GET['province'] ?? null;
$city = $_GET['city'] ?? null;

if ($region && $province && $city) {
    // Get Barangays
    if (isset($address_data[$region][$province][$city])) {
        echo json_encode($address_data[$region][$province][$city]);
    } else {
        echo json_encode([]);
    }
} elseif ($region && $province) {
    // Get Cities/Municipalities
    if (isset($address_data[$region][$province])) {
        echo json_encode(array_keys($address_data[$region][$province]));
    } else {
        echo json_encode([]);
    }
} elseif ($region) {
    // Get Provinces
    if (isset($address_data[$region])) {
        echo json_encode(array_keys($address_data[$region]));
    } else {
        echo json_encode([]);
    }
} else {
    // Get Regions
    echo json_encode(array_keys($address_data));
}
