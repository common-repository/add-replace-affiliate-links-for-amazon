<?php
/**
* @package URALP_UpdAmazonPlugin
*/

/*
Plugin Name: Add & Replace Affiliate Links for Amazon
Description: Add & Replace Affiliate Links for Amazon. Plugin that allows you to replace/add tag parameter on links that go to Amazon.
Version: 1.0.3
Author: Hekkup
Author URI: https://hekkup.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/



defined('ABSPATH') or die;
define('URALP_USE_REVISIONS', 1);
require_once('upd-amazon.php');
add_action('admin_menu', 'uralp_upd_amazon_setup_menu');
function uralp_upd_amazon_setup_menu() {
	add_menu_page(
		'Add & Replace Affiliate Links for Amazon', 'Add & Replace Affiliate Links for Amazon', 
		'manage_options', 'uralp-upd-amazon-plugin', 'uralp_init_page', 'dashicons-update-alt'
	);
	add_submenu_page(
		'uralp-upd-amazon-plugin', 'Add & Replace Affiliate Links for Amazon', 'Add & Replace Affiliate Links for Amazon', 
		'manage_options', 'uralp-upd-amazon-plugin', 'uralp_init_page' 
	);
	add_submenu_page('uralp-upd-amazon-plugin', 'Updated Page', 'Updated Pages', 'manage_options', 'uralp-upd-amazon-updated', 'uralp_show_updated');
}

add_action( 'uralp_hook', 'uralp_schedule_func', 10, 3 );
if ( class_exists('URALP_UpdAmazonPlugin')) {
	$updAmazonPlugin = new URALP_UpdAmazonPlugin();
}

function uralp_init_page() {
	require_once('templates/upd-amazon-plugin.php');
}

function uralp_show_updated() {
	require_once('templates/updated-records.php');
}

add_action( 'wp_ajax_uralp_update', 'uralp_update_callback' );
function uralp_update_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-update', 'security' );
	$new_id = sanitize_text_field($_POST['new-id']);
	if (!$new_id || $new_id === '') {
		die(json_encode(['success' => 0, 'msg' => 'Please, enter correct tag.']));
	}
	$posts_updated = URALP_UpdAmazonPlugin::updateDB($new_id);
	$is_scheduled = sanitize_text_field($_POST['is-scheduled']);
	wp_unschedule_hook('uralp_hook');
	if ($is_scheduled) {
		wp_schedule_event( time(), 'daily', 'uralp_hook', array($new_id) );
	}
		
	if ($posts_updated >= 0) {
		die(json_encode(['success'=> 1, 'msg' => "Plugin updated $posts_updated posts/pages."]));
	}
	else {
		die(json_encode(['success'=> 1, 'msg' => "Can't run update. Currently job is running."]));
	}
}

add_action( 'wp_ajax_uralp_backup', 'uralp_backup_callback' );
function uralp_backup_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-backup', 'security' );
	URALP_UpdAmazonPlugin::makeBackup();
	die(json_encode(['msg' => 'Backup has been done']));
}

add_action( 'wp_ajax_uralp_restore', 'uralp_restore_callback' );
function uralp_restore_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-restore', 'security' );
	URALP_UpdAmazonPlugin::restoreDB();
	die(json_encode(['msg' => 'Data has been restored from the latest backup']));
}

add_action( 'wp_ajax_uralp_no_follow', 'uralp_no_follow_callback');
function uralp_no_follow_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-no_follow', 'security' );
	$posts_updated = URALP_UpdAmazonPlugin::noFollow();
	if ($posts_updated >= 0) {
		die(json_encode(['success'=> 1, 'msg' => "Plugin updated $posts_updated posts/pages."]));
	}
	else {
		die(json_encode(['success'=> 1, 'msg' => "Can't run update. Currently job is running."]));
	}
}

add_action( 'wp_ajax_uralp_no_affiliate', 'uralp_no_affiliate_callback');
function uralp_no_affiliate_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-no_affiliate', 'security' );
	$posts_updated = URALP_UpdAmazonPlugin::noAffiliate();
	if ($posts_updated >= 0) {
		die(json_encode(['success'=> 1, 'msg' => "Plugin updated $posts_updated posts/pages."]));
	}
	else {
		die(json_encode(['success'=> 1, 'msg' => "Can't run update. Currently job is running."]));
	}
	
}

add_action( 'wp_ajax_uralp_expand', 'uralp_expand_callback');
function uralp_expand_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-expand', 'security' );
	$posts_updated = URALP_UpdAmazonPlugin::expand();
	if ($posts_updated >= 0) {
		die(json_encode(['success'=> 1, 'msg' => "Plugin updated $posts_updated posts/pages."]));
	}
	else {
		die(json_encode(['success'=> 1, 'msg' => "Can't run update. Currently job is running."]));
	}
}

add_action( 'wp_ajax_uralp_get_progress', 'uralp_get_progress_callback');
function uralp_get_progress_callback() {
	check_ajax_referer( 'upd-plugin-for-amazon-get_progress', 'security' );
	$job = URALP_UpdAmazonPlugin::getProgress();
	die(json_encode(['success'=> 1, 'msg' => $job]));
}

// activateion
register_activation_hook( __FILE__, [$updAmazonPlugin, 'activate'] );

// deactivation
register_deactivation_hook( __FILE__, [$updAmazonPlugin, 'deactivate'] );

function uralp_schedule_func($keyword) {
	URALP_UpdAmazonPlugin.updateDB($keyword);
}

