<?php
namespace CF7_Dynamic_Recipients;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles form integration
 */
class Form_Handler {
    private Settings $settings;
    
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('wpcf7_init', [$this, 'add_form_tag']);
        add_filter('wpcf7_validate_dynamic_recipient', [$this, 'validate_field'], 10, 2);
        add_filter('wpcf7_validate_dynamic_recipient*', [$this, 'validate_field'], 10, 2);
    }

    public function add_form_tag(): void {
        wpcf7_add_form_tag(
            ['dynamic_recipient', 'dynamic_recipient*'],
            [$this, 'render_field'],
            ['name-attr' => true]
        );
    }

    public function render_field($tag): string {
        if (!is_object($tag) || empty($tag->name)) {
            return '';
        }

        $validation_error = wpcf7_get_validation_error($tag->name);
        $class = wpcf7_form_controls_class($tag->type);

        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }

        $atts = [
            'class' => $class,
            'id' => $tag->get_id_option(),
            'name' => $tag->name,
        ];

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $recipients = $this->settings->get_recipients();
        if (empty($recipients)) {
            return '';
        }

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap %s">',
            sanitize_html_class($tag->name)
        );

        $html .= '<select ' . wpcf7_format_atts($atts) . '>';
        $html .= sprintf(
            '<option value="">%s</option>',
            esc_html__('Please select', 'cf7-dynamic-recipients')
        );

        foreach ($recipients as $recipient) {
            $html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($recipient['email']),
                esc_html($recipient['label'])
            );
        }

        $html .= '</select>';
        $html .= $validation_error;
        $html .= '</span>';

        return $html;
    }

    public function validate_field($result, $tag): \WPCF7_Validation {
        $submission = \WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return $result;
        }

        $data = $submission->get_posted_data();
        $name = $tag->name;
        $value = '';

        if (isset($data[$name])) {
            if (is_array($data[$name])) {
                $value = isset($data[$name][0]) ? sanitize_email($data[$name][0]) : '';
            } else {
                $value = sanitize_email($data[$name]);
            }
        }

        if ($tag->is_required() && empty($value)) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
            return $result;
        }

        if (!empty($value)) {
            $recipients = $this->settings->get_recipients();
            $valid_email = false;
            
            foreach ($recipients as $recipient) {
                if ($recipient['email'] === $value) {
                    $valid_email = true;
                    break;
                }
            }

            if (!$valid_email) {
                $result->invalidate($tag, esc_html__('Invalid recipient selection.', 'cf7-dynamic-recipients'));
            }
        }

        return $result;
    }
}