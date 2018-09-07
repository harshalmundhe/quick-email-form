<?php


//Delete the table when plugin is deleted
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

delete_option("dcf_version");
delete_option("dcf_recaptcha_det");
delete_option("dcf_from_email");
delete_option("qef_default_to_email");
