<?php

defined( 'WP_UNINSTALL_PLUGIN') or die;

global $wpdb;

$table_name = $wpdb->prefix . 'upd_amazon_plugin';
$updated_pages_table = $wpdb->prefix . 'upd_amazon_updated_pages';
$sql = "DROP TABLE IF EXISTS $table_name;";
$wpdb->query($sql);
$sql = "DROP TABLE IF EXISTS $updated_pages_table;";
$wpdb->query($sql);