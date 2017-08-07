<?php
namespace BULiaisonInquiry;

/**
 * SpectrumEMP API class.
 *
 * Sends, retrieves, and parses data from SpectrumEMP API.
 */
class SpectrumAPI
{
    // SpectrumEMP API URL setup.
    const API_URL = 'https://www.spectrumemp.com/api/';
    const REQUIREMENTS_PATH = 'inquiry_form/requirements';
    const SUBMIT_PATH = 'inquiry_form/submit';
    const CLIENT_RULES_PATH = 'field_rules/client_rules';
    const FIELD_OPTIONS_PATH = 'field_rules/field_options';

    // Can't setup with a single statement until php 5.6.
    /**
     * URL to fetch requirements.
     *
     * @var string
     */
    public static $requirements_url;

    /**
     * URL to submit form data to Liaison.
     *
     * @var string
     */
    public static $submit_url;

    /**
     * URL to fetch form validation rules.
     *
     * @var string
     */
    public static $client_rules_url;

    /**
     * URL to fetch options for form fields.
     *
     * @var string
     */
    public static $field_options_url;

    /**
     * Setup API URLs
     */
    public function __construct()
    {
        // Setup urls. After php 5.6, these can become class const definitions
        // (prior to 5.6 only flat strings can be class constants).
        self::$requirements_url = self::API_URL . self::REQUIREMENTS_PATH;
        self::$submit_url = self::API_URL . self::SUBMIT_PATH;
        self::$client_rules_url = self::API_URL . self::CLIENT_RULES_PATH;
        self::$field_options_url = self::API_URL . self::FIELD_OPTIONS_PATH;
    }
}
