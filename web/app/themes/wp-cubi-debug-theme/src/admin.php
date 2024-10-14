<?php

add_action('admin_notices', 'display_registration_email_error_notice');

// Function to display error messages in the WordPress admin area
function display_registration_email_error_notice() 
{
    // Check if there is an error message stored in the transient
    if ($error_message = get_transient('registration_email_error')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
        <?php
        // Delete the transient after displaying the error message
        delete_transient('registration_email_error');
    }
}