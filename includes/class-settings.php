<?php
namespace CF7_Dynamic_Recipients;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin settings
 */
class Settings {
    private const OPTION_KEY = 'cf7_dynamic_recipients_settings';

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function init_settings(): void {
        if (false === get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, []);
        }
    }

    public function register_settings(): void {
        register_setting(
            'cf7_dynamic_recipients_options',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => []
            ]
        );
    }

    public function sanitize_settings($input): array {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];
        foreach ($input as $value) {
            if (!is_array($value)) {
                continue;
            }

            $label = sanitize_text_field($value['label'] ?? '');
            $email = sanitize_email($value['email'] ?? '');

            if (!empty($label) && !empty($email)) {
                $sanitized[] = [
                    'label' => $label,
                    'email' => $email
                ];
            }
        }

        return array_values($sanitized);
    }

    public function get_recipients(): array {
        return get_option(self::OPTION_KEY, []);
    }

    public function get_recipient_by_index(int $index): ?array {
        $recipients = $this->get_recipients();
        return $recipients[$index] ?? null;
    }

    public function get_recipient_email_by_index(int $index): ?string {
        $recipient = $this->get_recipient_by_index($index);
        return $recipient ? $recipient['email'] : null;
    }

    public function update_recipients(array $recipients): bool {
        return update_option(self::OPTION_KEY, $recipients);
    }

    public function delete_settings(): bool {
        return delete_option(self::OPTION_KEY);
    }
}