<?php

namespace Globalis\WP\Test;

use Globalis\WP\Test\Lib\Classes\Email as Email;

define('REGISTRATION_ACF_KEY_LAST_NAME', 'field_64749cfff238e');
define('REGISTRATION_ACF_KEY_FIRST_NAME', 'field_64749d4bf238f');

add_filter('wp_insert_post_data', __NAMESPACE__ . '\\save_auto_title', 99, 2);
add_action('edit_form_after_title', __NAMESPACE__ . '\\display_custom_title_field');
add_action('publish_registrations', __NAMESPACE__ . '\\send_registration_email', 10, 3);

function save_auto_title($data, $postarr)
{
    if (! $data['post_type'] === 'registrations') {
        return $data;
    }
    if ('auto-draft' == $data['post_status']) {
        return $data;
    }

    if (!isset($postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME]) || !isset($postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME])) {
        return $data;
    }

    $data['post_title'] = "#" . $postarr['ID'] .  " (" . $postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME] . " " . $postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME] . ")";

    $data['post_name']  = wp_unique_post_slug(sanitize_title(str_replace('/', '-', $data['post_title'])), $postarr['ID'], $postarr['post_status'], $postarr['post_type'], $postarr['post_parent']);

    return $data;
}

function display_custom_title_field($post)
{
    if ($post->post_type !== 'registrations' || $post->post_status === 'auto-draft') {
        return;
    }
    ?>
    <h1><?= $post->post_title ?></h1>
    <?php
}

/**
 * Send an email after publish registration
 *
 * @param int $post_id The post ID.
 * @param post $post The post object.
 */
function send_registration_email($post_id, $post) 
{
    // Get the participant's registration details
    $participant_email = get_field('registration_email', $post_id);
    $first_name = get_field('registration_first_name', $post_id);
    $last_name = get_field('registration_last_name', $post_id);

    // Get the associated event ID
    $event_id = get_field('registration_event_id', $post_id);

    // Get the event details
    $event_name = get_the_title($event_id);
    $event_date = get_field('event_date', $event_id);
    $event_time = get_field('event_time', $event_id);

    $pdf_id = get_field('event_pdf_entrance_ticket', $event_id);
    $pdf_path = get_attached_file($pdf_id);

    // Ensure the PDF exists
    if (!$pdf_path) {
        set_transient('registration_email_error', 'No PDF found for event ID ' . $event_id, 60);
        return;
    }

    // Prepare the email content
    $subject = 'Your Registration for ' . $event_name;
    $message = 'Hello ' . $first_name . ' ' . $last_name . ",\n\n";
    $message .= 'Thank you for registering for ' . $event_name . " on " . $event_date . " at " . $event_time . ".\n";
    $message .= "Please find your entrance ticket attached.";

    // Create an instance of the Email class
    $email = new Email($participant_email, $subject, $message);

    try {
        $email->add_attachment($pdf_path);
    } catch (Exception $e) {
        set_transient('registration_email_error', 'Error adding attachment: ' . $e->getMessage(), 60);
        return;
    }

    try {
        $email->send();
    } catch (Exception $e) {
        set_transient('registration_email_error', 'Error sending email: ' . $e->getMessage(), 60);
    }
}