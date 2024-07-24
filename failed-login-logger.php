<?php
/*
Plugin Name: Failed Login Logger
Description: Logs failed login attempts and displays them in the admin panel.
Author URI: https://divipro24.com
Plugin URI: https://divipro24.com
Version: 1.0.0
Author: Dmitri Andrejev
Github URI: https://github.com/divipro24/failed-login-logger
License: GPLv2
*/

register_activation_hook(__FILE__, 'fll_create_table');

function fll_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        username tinytext NOT NULL,
        password tinytext NOT NULL,
        ip_address tinytext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wp_login_failed', 'fll_log_failed_login');

function fll_log_failed_login($username) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';

    $time = current_time('mysql');
    $password = isset($_POST['pwd']) ? $_POST['pwd'] : '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $wpdb->insert(
        $table_name,
        array(
            'time' => $time,
            'username' => $username,
            'password' => $password,
            'ip_address' => $ip_address
        )
    );
}

add_action('admin_menu', 'fll_register_admin_page');

function fll_register_admin_page() {
    add_menu_page(
        'Failed Logins',
        'Failed Logins',
        'manage_options',
        'failed-logins',
        'fll_display_failed_logins',
        'dashicons-shield',
        6
    );
}

function fll_display_failed_logins() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';

    // Handle delete request
    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);
        $wpdb->delete($table_name, array('id' => $delete_id));
        echo '<div class="updated"><p>Record deleted.</p></div>';
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h1>Failed Login Attempts</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Time</th><th>Username</th><th>Password</th><th>IP Address</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->time) . '</td>';
        echo '<td>' . esc_html($row->username) . '</td>';
        echo '<td>' . esc_html($row->password) . '</td>';
        echo '<td>' . esc_html($row->ip_address) . '</td>';
        echo '<td><a href="' . admin_url('admin.php?page=failed-logins&delete_id=' . $row->id) . '">Delete</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/divipro24/failed-login-logger',
    __FILE__,
    'failed-login-logger'
);

$myUpdateChecker->setBranch('main');