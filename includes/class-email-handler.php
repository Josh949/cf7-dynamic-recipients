<?php
namespace CF7_Dynamic_Recipients;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles email routing
 */
class Email_Handler {
    private Settings $settings;
    
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_filter('wpcf7_before_send_mail', [$this, 'route_email'], 10);
    }

    public function route_email($contact_form): \WPCF7_ContactForm {
        $submission = \WPCF7_Submission::get_instance();
        if (!$submission) {
            return $contact_form;
        }

        $posted_data = $submission->get_posted_data();
        
        // Look for both possible field names
        $selected_value = isset($posted_data['dynamic-recipient']) 
            ? $posted_data['dynamic-recipient'] 
            : (isset($posted_data['dynamic_recipient']) ? $posted_data['dynamic_recipient'] : '');

        if (empty($selected_value)) {
            return $contact_form;
        }

        // Handle array value
        if (is_array($selected_value)) {
            $selected_email = isset($selected_value[0]) ? $selected_value[0] : '';
        } else {
            $selected_email = $selected_value;
        }

        $selected_email = sanitize_email($selected_email);
        
        if (!is_email($selected_email)) {
            return $contact_form;
        }

        // Verify the email exists in our settings
        $recipients = $this->settings->get_recipients();
        $valid_recipient = false;
        
        foreach ($recipients as $recipient) {
            if ($recipient['email'] === $selected_email) {
                $valid_recipient = true;
                break;
            }
        }

        if (!$valid_recipient) {
            return $contact_form;
        }

        // Get current mail properties
        $mail = $contact_form->prop('mail');
        
        // Update recipient
        $mail['recipient'] = $selected_email;
        
        // Set updated properties
        $contact_form->set_properties([
            'mail' => $mail
        ]);

        return $contact_form;
    }
}