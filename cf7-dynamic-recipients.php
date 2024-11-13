<?php
/**
 * Plugin Name: Dynamic Recipients for Contact Form 7
 * Plugin URI: https://wordpress.org/plugins/cf7-dynamic-recipients
 * Description: Extends Contact Form 7 with dynamic recipient routing based on dropdown selections.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Author: Josh R Slone
 * Author URI: https://onlyplugins.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7-dynamic-recipients
 * Domain Path: /languages
 */

namespace CF7_Dynamic_Recipients;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF7DR_VERSION', '1.0.0');
define('CF7DR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7DR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7DR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    // Check if the class is from our plugin namespace
    if (strpos($class, 'CF7_Dynamic_Recipients\\') !== 0) {
        return;
    }

    // Remove namespace from class name
    $class_file = str_replace('CF7_Dynamic_Recipients\\', '', $class);
    // Convert class name format to file name format
    $class_file = 'class-' . str_replace('_', '-', strtolower($class_file)) . '.php';
    // Build file path
    $file = CF7DR_PLUGIN_DIR . 'includes/' . $class_file;

    // Include file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Main plugin class
 */
final class CF7_Dynamic_Recipients {
    /**
     * Single instance of the plugin
     */
    private static ?self $instance = null;

    /**
     * Plugin components
     */
    public Admin $admin;
    public Form_Handler $form_handler;
    public Settings $settings;
    public Email_Handler $email_handler;

    /**
     * Get plugin instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct object creation
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init(): void {
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }

        // Initialize components
        $this->settings = new Settings();
        $this->admin = new Admin($this->settings);
        $this->form_handler = new Form_Handler($this->settings);
        $this->email_handler = new Email_Handler($this->settings);

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Check if required plugins are active
     */
    private function check_dependencies(): bool {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            add_action('admin_notices', function() {
                $message = sprintf(
                    /* translators: %s: Contact Form 7 */
                    esc_html__('Dynamic Recipients for Contact Form 7 requires %s to be installed and activated.', 'cf7-dynamic-recipients'),
                    '<strong>' . esc_html__('Contact Form 7', 'cf7-dynamic-recipients') . '</strong>'
                );
                printf('<div class="notice notice-error"><p>%s</p></div>', wp_kses($message, [
                    'strong' => []
                ]));
            });
            return false;
        }

        return true;
    }

    /**
     * Plugin activation hook
     */
    public function activate(): void {
        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            deactivate_plugins(CF7DR_PLUGIN_BASENAME);
            wp_die(
                esc_html__('This plugin requires Contact Form 7 to be installed and activated.', 'cf7-dynamic-recipients'),
                esc_html__('Plugin Activation Error', 'cf7-dynamic-recipients'),
                ['back_link' => true]
            );
        }

        // Initialize settings on activation
        $this->settings->init_settings();

        // Clear permalinks
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'cf7-dynamic-recipients',
            false,
            dirname(CF7DR_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}

// Initialize the plugin
function cf7dr_init() {
    return CF7_Dynamic_Recipients::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'CF7_Dynamic_Recipients\\cf7dr_init');
