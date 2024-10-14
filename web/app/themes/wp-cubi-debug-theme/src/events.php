<?php

namespace Globalis\WP\Test;

use OpenSpout\Writer\WriterFactory;
use OpenSpout\Writer\Common\Creator\StyleBuilder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

add_action('admin_post_export_event_registrations', __NAMESPACE__ . '\\export_event_registrations_to_excel');

function export_event_registrations_to_excel() {
    // Check if the event ID is provided in the request
    if (!isset($_GET['event_id'])) {
        wp_die('Invalid request');
    }

    // Sanitize and retrieve the event ID
    $event_id = intval($_GET['event_id']);

    // Retrieve registrations associated with the event
    $registration_ids = get_posts([
        'post_type' => 'registrations',
        'meta_query' => [
            [
                'key' => 'registration_event_id', // Match registrations linked to this event
                'value' => $event_id,
                'compare' => '=',
            ]
        ],
        'posts_per_page' => -1, // Get all registrations without limiting the number
        'fields' => 'ids', // Only retrieve the post IDs to optimize performance
    ]);

    // Use Spout to generate the Excel file
    $file_path = wp_upload_dir()['basedir'] . '/registrations_event_' . $event_id . '.xlsx';
    $writer = new XLSXWriter();
    $writer->openToFile($file_path);

    // Create the Excel file headers
    $headerRow = Row::fromValues(['Last Name', 'First Name', 'Email', 'Phone']);
    $writer->addRow($headerRow);

    // Add each registration to the Excel file
    foreach ($registration_ids as $registration_id) {
        // Retrieve registration details
        $last_name = get_post_meta($registration_id, 'registration_last_name', true);
        $first_name = get_post_meta($registration_id, 'registration_first_name', true);
        $email = get_post_meta($registration_id, 'registration_email', true);
        $phone = get_post_meta($registration_id, 'registration_phone', true);

        // Create a new row with the registration data
        $row = Row::fromValues([$last_name, $first_name, $email, $phone]);
        $writer->addRow($row);
    }

    // Close the writer to finish the file
    $writer->close();

    $event_slug = get_post_field('post_name', $event_id);

    // Force the file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="registrations_event_' . $event_slug . '.xlsx"');
    readfile($file_path);

    // Delete the temporary file after the download
    unlink($file_path);

    exit;
}