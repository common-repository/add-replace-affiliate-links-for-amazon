<?php

class URALP_UpdAmazonPlugin 
{
	function activate() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$JOBS_TABLE = $wpdb->prefix . 'upd_amazon_jobs_log';
		$SETTINGS_TABLE = $wpdb->prefix . 'upd_amazon_settings';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $TABLE_NAME (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			post_text text NOT NULL,
			post_id integer NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS $UPDATED_PAGES_TABLE (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			post_id integer NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS $JOBS_TABLE (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			keyword text NOT NULL,
			percent integer NOT NULL,
			PRIMARY KEY  (id) 
		) $charset_collate;";
		dbDelta( $sql );
	}

	function deactivate() {

	}

	static function makeBackup() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';

		$sql = "DELETE FROM $TABLE_NAME;";
		$wpdb->query($sql);

		$posts = get_posts(array(
  			'numberposts' => -1,
  			'post_status' => 'any',
  			'post_type' => ['page', 'post', 'shortcoder'],
 		));
		
		foreach( $posts as $post ){
			if ( !self::isAllowedEdit( (int)$post->ID) )
				return;
			if ($post->post_content !== "") {
				if (count(self::findLinks($post->post_content)) > 0) {
					$wpdb->insert( 
						$TABLE_NAME, 
						array( 
							'time' => current_time( 'mysql' ),
							'post_text' => $post->post_content,
							'post_id' => $post->ID
						)
					);
				}
			}
		}
	}

	static function findLinks(string $str) {
		$pattern = '/"(https?):\/\/(www\.)?amzn.to([^"]*)"|"(https?):\/\/(www\.)?amazon.([^"]*)"/';
		$result = [];
		preg_match_all($pattern, $str, $out);
		foreach ($out[0] as $link) {
			if ( (strpos($link, '.amazon') !== false) || (strpos($link, '.amzn') !== false) 
					|| strpos($link, '//amazon.') !== false || strpos($link, '//amzn.') !== false ) {
				$result[] = $link;
			}
		}
		return $result;
	}

	static function getFullLink(string $url) {
		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$args = array(
			'timeout' => '10',
			'redirection' => '0',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'sslverify' => false,
			'user-agent' => $agent 
		);

		$response = wp_remote_get( $url, $args );
		return wp_remote_retrieve_header( $response, 'location' );
	}

	static function transformURL(string $in_link, string $newVal, bool $returnFull=false) {
		$link = $in_link;
		if (strpos($link, 'amzn.to') !== false && (strlen($newVal) || $returnFull)) {
			$location = self::getFullLink($link);
			if ($location) {
				$link = $location;
			}
		}
		if ((strpos($link, '.amazon') !== false) && (!$returnFull)) {
			$url_array = parse_url(trim($link));
			$params = array();
	     	parse_str($url_array['query'], $params);
	     	if (strlen($newVal) > 0) {
	     		if (isset($params['amp;tag'])) {
		     		$params['amp;tag'] = $newVal;	
		     	}
		     	else {
		     		$params['tag'] = $newVal;	
		     	}	
	     	}
	     	else {
	     		unset($params['tag']);
	     		unset($params['amp;tag']);
	     	}
	     	
	     	
	     	$query = '';
	     	foreach ($params as $key => $value) {
	     		if (strlen($query) > 0)  {
	     			$query.='&';
	     		}
	     		$query .= "$key=$value";
			}
			$path = $url_array["path"];
			if (strpos($url_array["path"], "tag=") !== false) {
				$path_chunks = explode("/", $path);
				$new_path_chunks = [];
				foreach ($path_chunks as $chunk) {
					if (strpos($chunk, "tag=") !== false)
						continue;
					$new_path_chunks[] = $chunk;
				}
				$path = implode("/", $new_path_chunks);
			}
			
			return $url_array["scheme"] .'://' . $url_array["host"] . $path ."?". $query;
		}
		else {
			return $link;
		}
	}

	static function updateDB($newID) {
		define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );
		define( 'DISALLOW_UNFILTERED_HTML', false );

		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$posts = get_posts(array(
  			'numberposts' => -1,
			'post_status' => 'any',
			#'include' => ['1638'],
  			'post_type' => ['page', 'post', 'shortcoder'],
 		));

 		$sql = "DELETE FROM $UPDATED_PAGES_TABLE;";
 		$wpdb->query($sql);
 		
 		if (!defined('URALP_USE_REVISIONS'))
			remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_cnt = 0;
		if (self::addJob($newID) < 0)
			return -1;
		$posts_len = count($posts);
		$current_progress = 1;
		$prev_progress = 1;
		$posts_idx = 1;
		foreach( $posts as $post ){ 
			if ($post->post_content !== "") {
				$links = self::findLinks($post->post_content);
				if (count(self::findLinks($post->post_content)) > 0) {
					$new_content = preg_replace_callback('/"(https?):\/\/(www\.)?amzn.to([^"]*)"|"(https?):\/\/(www\.)?amazon.([^"]*)"/', function($url) use ($newID) {
						$newLink = self::transformURL(str_replace('"', '', $url[0]), $newID);
    					return '"'.$newLink.'"';
					}, $post->post_content);
					self::saveHistory((int)$post->ID);
					$post = array( 'ID' => (int)$post->ID, 'post_content' =>  $new_content);
					wp_update_post(wp_slash( (array) $post));
					$post_cnt++;
				}
			}
			$current_progress = intval(($posts_idx / $posts_len) * 100);
			if ($current_progress >= $prev_progress + 5) {
				self::setProgress($current_progress);
				$prev_progress = $current_progress;
			}
			$posts_idx++;
		}
		if (!defined('URALP_USE_REVISIONS'))
			add_action( 'post_updated', 'wp_save_post_revision' );
		self::setProgress(100);
		return $post_cnt;
		
	}

	static function saveHistory(int $postID) {
		global $wpdb;
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$wpdb->insert( 
			$UPDATED_PAGES_TABLE, 
			array( 
				'time' => current_time( 'mysql' ),
				'post_id' => $postID
			)
		);
	}

	static function restoreDB() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		if (!defined('URALP_USE_REVISIONS'))
			remove_action( 'post_updated', 'wp_save_post_revision' );
		$origData = $wpdb->get_results( "SELECT post_id, post_text  FROM $TABLE_NAME;" );
		foreach ( $origData as $orig ) {
			if ( self::isAllowedEdit( (int)$orig->post_id )) {
				$post = array( 'ID' => (int)$orig->post_id, 'post_content' => $orig->post_text );
				wp_update_post($post);
			}
			
		}
		if (!defined('URALP_USE_REVISIONS'))
			add_action( 'post_updated', 'wp_save_post_revision' );
	}

	static function getUpdated() {
		global $wpdb;
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$POSTS_TABLE = $wpdb->prefix.'posts';
		$allowed_pages = [];
		$updated_pages = $wpdb->get_results( "SELECT * from $UPDATED_PAGES_TABLE;" );
		foreach ( $updated_pages as $page ) {
			if ( self::isAllowedEdit((int)$page->post_id )) {
				$allowed_pages[] = $page;
			}
		}
		return $allowed_pages;
	}

	static function isAllowedEdit($id) {
		if (
			( (get_post_type($id) === 'post') && (current_user_can('edit_post', $id))) ||
			( (get_post_type($id) === 'page') && (current_user_can('edit_page', $id)))
		) {
			return true;
		}
		else {
			return false;
		}
	}


	static function addJob($keyword) {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_jobs_log';

		$job = self::getLastJob();
		if ($job !== null && $job->percent != 100)
			return - 1;
		$wpdb->insert( 
			$TABLE_NAME, 
			array( 
				'time' => current_time( 'mysql' ),
				'keyword' => $keyword,
				'percent' => 0
			)
		);
		return 1;
	}

	static function getLastJob() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_jobs_log';
		$job = $wpdb->get_row( "SELECT *, (time < NOW() - INTERVAL 1 HOUR) as is_old FROM $TABLE_NAME order by id desc limit 1;" );
		if ($job->is_old === '0')
			return $job;
		else
			return null;
	}
	
	static function getLastKeyword() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_jobs_log';
		$job = $wpdb->get_row( "SELECT * FROM $TABLE_NAME where keyword != '' order by id desc limit 1;" );
		return $job;
	}

	static function setProgress($progress) {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_jobs_log';
		$job = $wpdb->get_row( "SELECT * FROM $TABLE_NAME order by id desc limit 1;" );
		$wpdb->update(
			$TABLE_NAME,
			array (
				'percent' => $progress
			),
			array(
				'id' => $job->id
			)
		);
	}
	

	static function noFollow() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		
		$posts = get_posts(array(
  			'numberposts' => -1,
  			'post_status' => 'any',
  			'post_type' => ['page', 'post', 'shortcoder'],
 		));

 		$sql = "DELETE FROM $UPDATED_PAGES_TABLE;";
 		$wpdb->query($sql);
 		
 		if (!defined('URALP_USE_REVISIONS'))
			remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_cnt = 0;
		

		if (self::addJob('') < 0)
			return -1;
		$posts_len = count($posts);
		$current_progress = 1;
		$prev_progress = 1;
		$posts_idx = 1;
		foreach( $posts as $post ){ 
			if ($post->post_content !== "") {
				if (count(self::findLinks($post->post_content)) > 0) {
					$dom = new DOMDocument();
					$dom->loadHTML($post->post_content);
					foreach ($dom->getElementsByTagName('a') as $item) {
						$link = $item->getAttribute('href');
						if ( (strpos($link, '.amazon') !== false) || (strpos($link, '.amzn') !== false) 
							|| strpos($link, '//amazon.') !== false || strpos($link, '//amzn.') !== false ) {
							$item->setAttribute('rel', 'nofollow');
						    $new_content = $dom->saveHTML();
						    self::saveHistory((int)$post->ID);
							$post = array( 'ID' => (int)$post->ID, 'post_content' =>  $new_content);
							wp_update_post($post);
							$post_cnt++;
						}
					}
				}
			}
			$current_progress = intval(($posts_idx / $posts_len) * 100);
			if ($current_progress >= $prev_progress + 5) {
				self::setProgress($current_progress);
				$prev_progress = $current_progress;
			}
			$posts_idx++;
		}
		if (!defined('URALP_USE_REVISIONS'))
			add_action( 'post_updated', 'wp_save_post_revision' );
		self::setProgress(100);
		return $post_cnt;
	}

	static function noAffiliate() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$posts = get_posts(array(
  			'numberposts' => -1,
  			'post_status' => 'any',
  			'post_type' => ['page', 'post', 'shortcoder'],
 		));

 		$sql = "DELETE FROM $UPDATED_PAGES_TABLE;";
 		$wpdb->query($sql);
 		
 		if (!defined('URALP_USE_REVISIONS'))
			remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_cnt = 0;
		if (self::addJob('') < 0)
			return -1;
		$posts_len = count($posts);
		$current_progress = 1;
		$prev_progress = 1;
		$posts_idx = 1;
		foreach( $posts as $post ){ 
			if ($post->post_content !== "") {
				$links = self::findLinks($post->post_content);
				if (count(self::findLinks($post->post_content)) > 0) {
					$new_content = preg_replace_callback('/"(https?):\/\/(www\.)?amzn.to([^"]*)"|"(https?):\/\/(www\.)?amazon.([^"]*)"/', function($url) {
						$newLink = self::transformURL(str_replace('"', '', $url[0]), '');
    					return '"'.$newLink.'"';
					}, $post->post_content);
					self::saveHistory((int)$post->ID);
					$post = array( 'ID' => (int)$post->ID, 'post_content' =>  $new_content);
					wp_update_post($post);
					$post_cnt++;
				}
			}
			$current_progress = intval(($posts_idx / $posts_len) * 100);
			if ($current_progress >= $prev_progress + 5) {
				self::setProgress($current_progress);
				$prev_progress = $current_progress;
			}
			$posts_idx++;
		}
		if (!defined('URALP_USE_REVISIONS'))
			add_action( 'post_updated', 'wp_save_post_revision' );
		self::setProgress(100);
		return $post_cnt;
	}

	static function expand() {
		global $wpdb;
		$TABLE_NAME = $wpdb->prefix . 'upd_amazon_plugin';
		$UPDATED_PAGES_TABLE = $wpdb->prefix . 'upd_amazon_updated_pages';
		$posts = get_posts(array(
  			'numberposts' => -1,
  			'post_status' => 'any',
  			'post_type' => ['page', 'post', 'shortcoder'],
 		));

 		$sql = "DELETE FROM $UPDATED_PAGES_TABLE;";
 		$wpdb->query($sql);
 		
 		if (!defined('URALP_USE_REVISIONS'))
			remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_cnt = 0;
		if (self::addJob('') < 0)
			return -1;
		$posts_len = count($posts);
		$current_progress = 1;
		$prev_progress = 1;
		$posts_idx = 1;
		foreach( $posts as $post ){ 
			if ($post->post_content !== "") {
				$links = self::findLinks($post->post_content);
				if (count(self::findLinks($post->post_content)) > 0) {
					$new_content = preg_replace_callback('/"(https?):\/\/(www\.)?amzn.to([^"]*)"|"(https?):\/\/(www\.)?amazon.([^"]*)"/', function($url) {
						$newLink = self::transformURL(str_replace('"', '', $url[0]), '', true);
    					return '"'.$newLink.'"';
					}, $post->post_content);
					self::saveHistory((int)$post->ID);
					$post = array( 'ID' => (int)$post->ID, 'post_content' =>  $new_content);
					wp_update_post($post);
					$post_cnt++;
				}
			}
			$current_progress = intval(($posts_idx / $posts_len) * 100);
			if ($current_progress >= $prev_progress + 5) {
				self::setProgress($current_progress);
				$prev_progress = $current_progress;
			}
			$posts_idx++;
		}
		if (!defined('URALP_USE_REVISIONS'))
			add_action( 'post_updated', 'wp_save_post_revision' );
		self::setProgress(100);
		return $post_cnt;
	}

	static function getProgress() {
		$job = self::getLastJob();
		return $job;
	}
}
