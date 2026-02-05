<?php
/**
 * Meta Conversions API Integration
 * Modern server-side event tracking for 2024-2025
 * Works in tandem with Meta Pixel for redundancy and deduplication
 * 
 * @link https://developers.facebook.com/docs/marketing-api/conversions-api
 * @version 2.0
 */

class MetaIntegration {
    private $pixelId;
    private $accessToken;
    private $testMode = false;
    private $apiVersion = 'v19.0';

    /**
     * Initialize Meta Conversions API
     */
    public function __construct() {
        if (!function_exists('loadEnvFile')) {
            require_once __DIR__ . '/functions.php';
        }
        $env = loadEnvFile(__DIR__ . '/../.env');

        $this->pixelId = $env['FACEBOOK_PIXEL_ID'] ?? '';
        $this->accessToken = $env['FACEBOOK_ACCESS_TOKEN'] ?? '';
        $this->testMode = ($env['FACEBOOK_CONVERSIONS_TEST_MODE'] ?? 'false') === 'true';
    }

    /**
     * Generate unique event ID for deduplication
     * This ID must match between Pixel and Conversions API
     */
    public static function generateEventId($eventName, $context = []) {
        $contextString = implode('_', array_merge([$eventName], $context));
        return md5($contextString . microtime(true));
    }

    /**
     * Send event to Conversions API
     * 
     * @param array $event The event to send
     * @return array Response data
     */
    public function sendEvent($event) {
        if (empty($this->pixelId) || empty($this->accessToken)) {
            error_log('[Meta Conversions API] Not configured - missing pixel ID or access token');
            return ['success' => false, 'error' => 'Not configured'];
        }

        try {
            $url = 'https://graph.facebook.com/' . $this->apiVersion . '/' . $this->pixelId . '/events';
            
            $events = [$event];
            $payload = [
                'data' => $events,
                'access_token' => $this->accessToken
            ];

            // Add test event code if in test mode
            if ($this->testMode) {
                $payload['test_event_code'] = 'TEST12345';
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);

            error_log('[Meta Conversions API] Event sent: ' . $event['event_name']);
            error_log('[Meta Conversions API] HTTP Code: ' . $httpCode);
            error_log('[Meta Conversions API] Response: ' . json_encode($responseData));

            return [
                'success' => ($httpCode >= 200 && $httpCode < 300),
                'http_code' => $httpCode,
                'response' => $responseData
            ];

        } catch (Exception $e) {
            error_log('[Meta Conversions API] Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send multiple events in a batch (more efficient)
     */
    public function sendBatchEvents($events) {
        if (empty($this->pixelId) || empty($this->accessToken)) {
            error_log('[Meta Conversions API] Not configured');
            return ['success' => false, 'error' => 'Not configured'];
        }

        try {
            $url = 'https://graph.facebook.com/' . $this->apiVersion . '/' . $this->pixelId . '/events';
            
            $payload = [
                'data' => $events,
                'access_token' => $this->accessToken
            ];

            // Add test event code if in test mode
            if ($this->testMode) {
                $payload['test_event_code'] = 'TEST12345';
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);

            error_log('[Meta Conversions API] Batch sent: ' . count($events) . ' events');
            error_log('[Meta Conversions API] HTTP Code: ' . $httpCode);

            return [
                'success' => ($httpCode >= 200 && $httpCode < 300),
                'http_code' => $httpCode,
                'response' => $responseData
            ];

        } catch (Exception $e) {
            error_log('[Meta Conversions API] Batch error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Hash customer data (SHA256) - required by Meta
     */
    private function hashData($data) {
        return hash('sha256', strtolower(trim($data)));
    }

    /**
     * Create UserData object with customer information
     * Higher match quality with more parameters
     */
    private function createUserData($userData = []) {
        $user = [];

        // Email (must be hashed)
        if (!empty($userData['email'])) {
            $user['em'] = $this->hashData($userData['email']);
        }

        // Phone (must be hashed)
        if (!empty($userData['phone'])) {
            $user['ph'] = $this->hashData($userData['phone']);
        }

        // First name (must be hashed)
        if (!empty($userData['first_name'])) {
            $user['fn'] = $this->hashData($userData['first_name']);
        }

        // Last name (must be hashed)
        if (!empty($userData['last_name'])) {
            $user['ln'] = $this->hashData($userData['last_name']);
        }

        // City
        if (!empty($userData['city'])) {
            $user['ct'] = $userData['city'];
        }

        // State
        if (!empty($userData['state'])) {
            $user['st'] = $userData['state'];
        }

        // Country code
        if (!empty($userData['country'])) {
            $user['country'] = $userData['country'];
        }

        // Zip code
        if (!empty($userData['zip'])) {
            $user['zp'] = $userData['zip'];
        }

        // IP address (not hashed)
        if (!empty($userData['ip'])) {
            $user['client_ip_address'] = $userData['ip'];
        }

        // User agent (not hashed)
        if (!empty($userData['user_agent'])) {
            $user['client_user_agent'] = $userData['user_agent'];
        }

        // Click ID from first-party cookie
        if (!empty($userData['fbclid'])) {
            $user['fbc'] = $userData['fbclid'];
        }

        // Browser ID from first-party cookie
        if (!empty($userData['fbp'])) {
            $user['fbp'] = $userData['fbp'];
        }

        return $user;
    }

    /**
     * Create Content object for product events
     */
    private function createContent($productId, $name, $quantity = 1, $price = 0, $category = '') {
        return [
            'id' => $productId,
            'title' => $name,
            'quantity' => $quantity,
            'item_price' => $price,
            'category' => $category
        ];
    }

    /**
     * Track ViewContent event (product page view)
     * @param string|null $eventId Optional event ID for Pixel/API deduplication; if null, one is generated
     */
    public function trackViewContent($productId, $productName, $price, $category, $userData = [], $eventId = null) {
        if ($eventId === null) {
            $eventId = self::generateEventId('ViewContent', [$productId, session_id()]);
        }

        $user = $this->createUserData($userData);
        $content = $this->createContent($productId, $productName, 1, $price, $category);

        $event = [
            'event_name' => 'ViewContent',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [
                'contents' => [$content],
                'value' => $price,
                'currency' => 'GBP'
            ],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track AddToCart event
     */
    public function trackAddToCart($productId, $productName, $quantity, $price, $category, $userData = []) {
        $eventId = self::generateEventId('AddToCart', [$productId, $quantity]);

        $user = $this->createUserData($userData);
        $content = $this->createContent($productId, $productName, $quantity, $price, $category);

        $event = [
            'event_name' => 'AddToCart',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [
                'contents' => [$content],
                'value' => $price * $quantity,
                'currency' => 'GBP'
            ],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track InitiateCheckout event
     */
    public function trackInitiateCheckout($cartItems, $totalValue, $userData = []) {
        $eventId = self::generateEventId('InitiateCheckout', [session_id()]);

        $user = $this->createUserData($userData);
        $contents = [];

        foreach ($cartItems as $item) {
            $contents[] = $this->createContent(
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $item['category'] ?? ''
            );
        }

        $event = [
            'event_name' => 'InitiateCheckout',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [
                'contents' => $contents,
                'value' => $totalValue,
                'currency' => 'GBP',
                'num_items' => count($cartItems)
            ],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track Purchase event
     */
    public function trackPurchase($orderId, $cartItems, $totalValue, $userData = []) {
        $eventId = self::generateEventId('Purchase', [$orderId]);

        $user = $this->createUserData($userData);
        $contents = [];

        foreach ($cartItems as $item) {
            $contents[] = $this->createContent(
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $item['category'] ?? ''
            );
        }

        $event = [
            'event_name' => 'Purchase',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [
                'contents' => $contents,
                'value' => $totalValue,
                'currency' => 'GBP',
                'num_items' => count($cartItems),
                'order_id' => $orderId
            ],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track Lead event (contact form, newsletter signup)
     */
    public function trackLead($leadType, $userData = []) {
        $eventId = self::generateEventId('Lead', [$leadType, session_id()]);

        $user = $this->createUserData($userData);

        $event = [
            'event_name' => 'Lead',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [
                'value' => 0,
                'currency' => 'GBP',
                'lead_type' => $leadType
            ],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track Contact event (contact form submission)
     */
    public function trackContact($userData = []) {
        $eventId = self::generateEventId('Contact', [session_id()]);

        $user = $this->createUserData($userData);

        $event = [
            'event_name' => 'Contact',
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => [],
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }

    /**
     * Track CustomEvent (for any custom conversion)
     */
    public function trackCustomEvent($eventName, $parameters = [], $userData = []) {
        $eventId = self::generateEventId($eventName, [session_id()]);

        $user = $this->createUserData($userData);

        $event = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'user_data' => $user,
            'custom_data' => $parameters,
            'action_source' => 'website'
        ];

        return $this->sendEvent($event);
    }
}
?>
