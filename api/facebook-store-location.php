<?php
/**
 * Meta Page Locations API helper – add or list store locations for a Facebook Page.
 * Use this to satisfy "Shop location catalogues require a Page with a store Pages structure".
 *
 * @link https://developers.facebook.com/docs/graph-api/reference/v24.0/page/locations
 */

if (!function_exists('loadEnvFile')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$env = loadEnvFile(__DIR__ . '/../.env');
$pageId = $env['FACEBOOK_PAGE_ID'] ?? '';
$accessToken = $env['FACEBOOK_ACCESS_TOKEN'] ?? '';
$apiVersion = 'v24.0';
$graphUrl = 'https://graph.facebook.com/' . $apiVersion;

header('Content-Type: application/json; charset=utf-8');

if (empty($pageId) || empty($accessToken)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing FACEBOOK_PAGE_ID or FACEBOOK_ACCESS_TOKEN in .env',
        'usage' => [
            'GET' => 'List existing locations for the Page',
            'POST' => 'Add one store. Body: store_number, street, city, country, zip, phone, latitude, longitude; optional: state (required for USA), place_topics (array of ints)',
        ],
    ], JSON_PRETTY_PRINT);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // List existing locations
    $url = $graphUrl . '/' . $pageId . '/locations?access_token=' . urlencode($accessToken);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);

    if ($httpCode >= 400) {
        http_response_code((int) $httpCode);
        echo json_encode([
            'error' => 'Graph API error',
            'http_code' => $httpCode,
            'response' => $data,
            'hint' => 'Ensure FACEBOOK_ACCESS_TOKEN is a Page access token with pages_manage_metadata (or similar) for this Page.',
        ], JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode([
        'page_id' => $pageId,
        'locations' => $data['data'] ?? [],
        'usage' => 'POST with JSON body to add a store. See META_COMMERCE_TEST_AND_VALIDATE.md.',
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $body = json_decode($input ?: '{}', true);
    if (!is_array($body)) {
        $body = [];
    }

    $storeNumber = isset($body['store_number']) ? (string) $body['store_number'] : '1';
    $street = trim((string) ($body['street'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $country = trim((string) ($body['country'] ?? ''));
    $state = trim((string) ($body['state'] ?? ''));
    $zip = trim((string) ($body['zip'] ?? ''));
    $phone = trim((string) ($body['phone'] ?? ''));
    $latitude = isset($body['latitude']) ? (string) $body['latitude'] : '';
    $longitude = isset($body['longitude']) ? (string) $body['longitude'] : '';
    $placeTopics = isset($body['place_topics']) && is_array($body['place_topics'])
        ? $body['place_topics']
        : [1];

    $missing = [];
    if ($street === '') {
        $missing[] = 'street';
    }
    if ($city === '') {
        $missing[] = 'city';
    }
    if ($country === '') {
        $missing[] = 'country';
    }
    if ($phone === '') {
        $missing[] = 'phone';
    }
    if ($latitude === '') {
        $missing[] = 'latitude';
    }
    if ($longitude === '') {
        $missing[] = 'longitude';
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing required fields: ' . implode(', ', $missing),
            'required' => ['store_number', 'street', 'city', 'country', 'zip', 'phone', 'latitude', 'longitude'],
            'note' => 'For US addresses, "state" is also required.',
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $location = [
        'street' => $street,
        'city' => $city,
        'country' => $country,
    ];
    if ($state !== '') {
        $location['state'] = $state;
    }
    if ($zip !== '') {
        $location['zip'] = $zip;
    }

    // Graph API expects form-urlencoded. location as JSON string; place_topics as array.
    $params = [
        'main_page_id' => $pageId,
        'store_number' => $storeNumber,
        'location' => json_encode($location),
        'phone' => $phone,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'place_topics' => array_map('intval', $placeTopics),
        'access_token' => $accessToken,
    ];

    $url = $graphUrl . '/' . $pageId . '/locations';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);

    if ($httpCode >= 400) {
        http_response_code((int) $httpCode);
        echo json_encode([
            'error' => 'Graph API error when adding location',
            'http_code' => $httpCode,
            'response' => $data,
            'hint' => 'Use a Page access token. Creating new locations may require additional app/Page permissions.',
        ], JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode([
        'success' => ($data['success'] ?? false) === true,
        'response' => $data,
        'message' => 'Store location added. Refresh Commerce Manager / Shop locations and try connecting the catalogue again.',
    ], JSON_PRETTY_PRINT);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed. Use GET to list locations or POST to add a store.'], JSON_PRETTY_PRINT);
