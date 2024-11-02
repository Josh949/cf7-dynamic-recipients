<?php
namespace CF7_Dynamic_Recipients;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin functionality
 */
class Admin {
    private Settings $settings;

    public function __construct(Settings $settings) {
        $this->settings = $settings;
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_admin_menu(): void {
        add_submenu_page(
            'wpcf7',
            esc_html__('Dynamic Recipients', 'cf7-dynamic-recipients'),
            esc_html__('Dynamic Recipients', 'cf7-dynamic-recipients'),
            'manage_options',
            'cf7-dynamic-recipients',
            [$this, 'render_admin_page']
        );
    }

    public function enqueue_admin_assets(string $hook): void {
        if ('contact_page_cf7-dynamic-recipients' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'cf7-dynamic-recipients-admin',
            CF7DR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CF7DR_VERSION
        );

        wp_enqueue_script(
            'cf7-dynamic-recipients-admin',
            CF7DR_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            CF7DR_VERSION,
            true
        );

        wp_localize_script(
            'cf7-dynamic-recipients-admin',
            'cf7DynamicRecipientsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cf7_dynamic_recipients_nonce'),
                'i18n' => [
                    'confirmDelete' => esc_html__('Are you sure you want to remove this recipient?', 'cf7-dynamic-recipients'),
                    'savingChanges' => esc_html__('Saving changes...', 'cf7-dynamic-recipients'),
                    'changesSaved' => esc_html__('Changes saved successfully.', 'cf7-dynamic-recipients'),
                    'error' => esc_html__('An error occurred. Please try again.', 'cf7-dynamic-recipients')
                ]
            ]
        );
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cf7-dynamic-recipients'));
        }

        $recipients = $this->settings->get_recipients();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php esc_html_e('Add the following shortcode to your Contact Form 7 forms:', 'cf7-dynamic-recipients'); ?>
                    <code>[dynamic_recipient* dynamic-recipient]</code>
                </p>
            </div>

            <form method="post" action="options.php" id="dynamic-recipients-form">
                <?php
                settings_fields('cf7_dynamic_recipients_options');
                do_settings_sections('cf7_dynamic_recipients_options');
                ?>

                <div id="dynamic-recipients-container">
                    <?php
                    if (!empty($recipients)) {
                        foreach ($recipients as $index => $recipient) {
                            $this->render_recipient_row((int) $index, $recipient);
                        }
                    }
                    ?>
                </div>

                <button type="button" class="button" id="add-recipient">
                    <?php esc_html_e('Add New Recipient', 'cf7-dynamic-recipients'); ?>
                </button>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function render_recipient_row(int $index, array $recipient): void {
        ?>
        <div class="recipient-row">
            <input type="text"
                   name="<?php echo esc_attr(sprintf('cf7_dynamic_recipients_settings[%d][label]', $index)); ?>"
                   value="<?php echo esc_attr($recipient['label'] ?? ''); ?>"
                   placeholder="<?php esc_attr_e('Label', 'cf7-dynamic-recipients'); ?>"
                   required
            />
            <input type="email"
                   name="<?php echo esc_attr(sprintf('cf7_dynamic_recipients_settings[%d][email]', $index)); ?>"
                   value="<?php echo esc_attr($recipient['email'] ?? ''); ?>"
                   placeholder="<?php esc_attr_e('Email', 'cf7-dynamic-recipients'); ?>"
                   required
            />
            <button type="button" class="button remove-recipient">
                <?php esc_html_e('Remove', 'cf7-dynamic-recipients'); ?>
            </button>
        </div>
        <?php
    }
}