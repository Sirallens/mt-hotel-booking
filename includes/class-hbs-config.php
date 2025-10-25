<?php
/**
 * Global config for Altavista Hotel Booking System
 *
 * Defines constants used across plugin modules.
 */

if (!defined('ABSPATH')) exit;

class HBS_Config {
    /** Option key used for saving plugin settings */
    const OPTION_KEY   = 'hbs_settings';

    /** Nonce action for AJAX and forms */
    const NONCE_ACTION = 'hbs_nonce_action';

    /** Nonce field name for AJAX and forms */
    const NONCE_KEY    = 'hbs_nonce';
}
