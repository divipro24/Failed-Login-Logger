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
register_uninstall_hook(__FILE__, 'fll_uninstall');

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

function fll_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';

    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
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

    // Handle clear all request
    if (isset($_POST['clear_all'])) {
        $wpdb->query("DELETE FROM $table_name");
        echo '<div class="updated"><p>All records cleared.</p></div>';
    }

    // Pagination
    $items_per_page = 50;
    $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $offset = ($page - 1) * $items_per_page;

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $items_per_page);

    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name LIMIT %d OFFSET %d", $items_per_page, $offset));

    echo '<div class="wrap">';
    echo '<h1>Failed Login Attempts</h1>';

    // Clear all button
    echo '<form method="post">';
    echo '<input type="hidden" name="clear_all" value="true">';
    echo '<input type="submit" class="button button-primary" value="Clear All">';
    echo '</form>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Time</th><th>Username</th><th>Password</th><th>IP Address</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->time) . '</td>';
            echo '<td>' . esc_html($row->username) . '</td>';
            echo '<td>' . esc_html($row->password) . '</td>';
            echo '<td>' . esc_html($row->ip_address) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=failed-logins&delete_id=' . $row->id) . '">Delete</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No records found.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Pagination links
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        $pagination_args = array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => $total_pages,
            'current' => $page
        );
        echo paginate_links($pagination_args);
        echo '</div></div>';
    }

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