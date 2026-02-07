<?php
/**
 * Settings Repository
 * Data access layer for application settings using MySQL
 */

require_once __DIR__ . '/../database.php';

class SettingsRepository {

    private static ?array $cache = null;

    /**
     * Get all settings as a single associative array
     */
    public static function getAll(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $sql = "SELECT setting_key, setting_value FROM settings";
        $rows = Database::fetchAll($sql);

        $settings = [];
        foreach ($rows as $row) {
            $value = $row['setting_value'];
            // Decode JSON values
            $decoded = json_decode($value, true);
            $settings[$row['setting_key']] = $decoded !== null ? $decoded : $value;
        }

        // Merge with defaults for any missing settings
        $settings = array_merge(self::getDefaults(), $settings);

        self::$cache = $settings;
        return $settings;
    }

    /**
     * Get a single setting value
     */
    public static function get(string $key, $default = null) {
        $settings = self::getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Set a single setting value
     */
    public static function set(string $key, $value): bool {
        self::$cache = null; // Clear cache

        $jsonValue = json_encode($value);

        // Check if setting exists
        if (Database::exists('settings', 'setting_key = ?', [$key])) {
            return Database::update('settings', ['setting_value' => $jsonValue], 'setting_key = ?', [$key]) >= 0;
        } else {
            Database::insert('settings', [
                'setting_key' => $key,
                'setting_value' => $jsonValue,
            ]);
            return true;
        }
    }

    /**
     * Update multiple settings at once
     */
    public static function updateMultiple(array $settings): bool {
        self::$cache = null; // Clear cache

        return Database::transaction(function() use ($settings) {
            foreach ($settings as $key => $value) {
                self::set($key, $value);
            }
            return true;
        });
    }

    /**
     * Delete a setting
     */
    public static function delete(string $key): bool {
        self::$cache = null; // Clear cache
        return Database::delete('settings', 'setting_key = ?', [$key]) > 0;
    }

    /**
     * Get default settings
     */
    public static function getDefaults(): array {
        return [
            'site_name' => 'Angel Marketplace',
            'site_description' => 'Your go-to destination for meaningful products and experiences',
            'site_email' => '',
            'site_phone' => '',
            'site_phone_alt' => '',
            'site_address' => '',
            'currency_symbol' => '£',
            'currency_code' => 'GBP',
            'currencies' => [
                ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound', 'default' => true],
            ],
            'available_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'items_per_page' => 12,
            'featured_products_count' => 8,
            'trending_products_count' => 12,
            'related_products_count' => 4,
            'homepage_categories_count' => 6,
            'social_media' => [
                'facebook' => '#',
                'twitter' => '#',
                'instagram' => '#',
                'youtube' => '#',
                'linkedin' => '#',
            ],
            'payment_methods' => ['stripe', 'paypal', 'bank_transfer'],
            'shipping' => [
                'free_shipping_threshold' => 0,
                'standard_shipping_cost' => 5,
                'costs' => [],
                'enable_pickup' => true,
                'enable_delivery' => true,
                'allow_method_selection' => true,
                'default_method' => 'delivery',
                'pickup_label' => 'Pickup',
                'pickup_instructions' => '',
                'show_shipping_pre_checkout' => false,
            ],
            'maintenance_mode' => false,
            'analytics' => [
                'google_analytics_id' => '',
                'facebook_pixel_id' => '',
            ],
        ];
    }

    /**
     * Get currency settings
     */
    public static function getCurrencies(): array {
        return self::get('currencies', []);
    }

    /**
     * Get default currency
     */
    public static function getDefaultCurrency(): array {
        $currencies = self::getCurrencies();
        foreach ($currencies as $currency) {
            if (!empty($currency['default'])) {
                return $currency;
            }
        }
        return $currencies[0] ?? ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound'];
    }

    /**
     * Get shipping settings
     */
    public static function getShippingSettings(): array {
        $shipping = self::get('shipping', []);
        $defaults = self::getDefaults()['shipping'];
        return array_merge($defaults, $shipping);
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool {
        return (bool)self::get('maintenance_mode', false);
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache(): void {
        self::$cache = null;
    }

    /**
     * Import settings from JSON file (for migration)
     */
    public static function importFromJson(string $jsonFilePath): bool {
        if (!file_exists($jsonFilePath)) {
            return false;
        }

        $content = file_get_contents($jsonFilePath);
        $settings = json_decode($content, true);

        if (!is_array($settings)) {
            return false;
        }

        return self::updateMultiple($settings);
    }

    /**
     * Export settings to JSON
     */
    public static function exportToJson(): string {
        return json_encode(self::getAll(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
