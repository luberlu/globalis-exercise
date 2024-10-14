<?php

namespace Globalis\WP\Test\Lib\Classes;

class Email {
    private $to;
    private $subject;
    private $message;
    private $headers;
    private $attachments;

    /**
     * Constructor method to initialize the email recipient, subject, and message.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The email subject.
     * @param string $message The email body content.
     */
    public function __construct($to, $subject, $message) {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        // Set default headers to send the email as HTML
        $this->headers = array('Content-Type: text/html; charset=UTF-8');
        // Initialize an empty array to store attachments
        $this->attachments = array();
    }

    /**
     * Method to add an attachment to the email.
     *
     * @param string $file_path The path to the file to attach.
     * @throws Exception If the file is not found at the provided path.
     */
    public function add_attachment($file_path) {
        // Check if the file exists at the given path
        if (file_exists($file_path)) {
            $this->attachments[] = $file_path; // Add the file to the attachments array
        } else {
            throw new Exception("File not found: " . $file_path); // Throw an exception if the file does not exist
        }
    }

    /**
     * Method to send the email.
     *
     * @throws Exception If the recipient's email address is missing or if wp_mail fails.
     */
    public function send() {
        // Check if the recipient's email address is provided
        if (empty($this->to)) {
            throw new Exception('Recipient email address is missing.');
        }

        // Use WordPress wp_mail function to send the email, including attachments
        if (!wp_mail($this->to, $this->subject, $this->message, $this->headers, $this->attachments)) {
            throw new Exception('Failed to send the email.'); // Throw an exception if the email fails to send
        }
    }

    /**
     * Method to add a custom header to the email.
     *
     * @param string $header The custom email header to add.
     */
    public function add_header($header) {
        // Add a custom header to the headers array
        $this->headers[] = $header;
    }
}
