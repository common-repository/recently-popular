<?php
/*
Plugin Name: Recently Popular
Description: Displays the most popular posts based on history from now to X amount of time in the past.
Author: Eric Biven, Caio Costa
Author URI: http://eric.biven.us/
Plugin URI: http://eric.biven.us/2008/12/03/recently-popular-wordpress-plugin/
Version: 0.7.2
*/

// Copyright (c) 2008 Eric Biven
// Released under the FreeBSD license:
// http://www.freebsd.org/copyright/freebsd-license.html

require_once('include.php');
include_once('recently-popular-widget.php');

class RecentlyPopular {

    private $table_suffix = 'recently_popular';
    private $short_path = 'recently-popular/recently-popular.php';

    private function get_file_path() { return ABSPATH . 'wp-content/plugins/'.$this->short_path; }
    private function get_table_name() { global $wpdb; return $wpdb->prefix.$this->table_suffix; }

    /*
     * Standard PHP object property handlers. Allow any property to be created/set/removed for
     * flexibility in case someone decides to extend the plugin. Caveat emptor.
     */
    private $data = array();
    public function __set($name, $value) { $this->data[$name] = $value; }
    public function __get($name) {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }

    public function __construct() {
        // Load default property values.
        $this->data = &RecentlyPopularUtil::$defaults;
        // Load the language files.
        load_plugin_textdomain('recently-popular', false, 'recently-popular/languages');
        // Register hooks and actions.
        register_activation_hook($this->get_file_path(), array(&$this, 'activate'));
        register_deactivation_hook($this->get_file_path(), array(&$this, 'deactivate'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('wp', array(&$this, 'record_hit'));
        add_action('wpmu_new_blog', array(&$this, 'create_blog'));
        add_action('wpmu_delete_blog', array(&$this, 'delete_blog'));

        $this->widget_title = __('Recently Popular');
    }

    /*
     * Create the link in the Settings menu and link it to the options page.
     */
    public function admin_menu() {
        add_options_page(
        	'Recently Popular Options',
        	'Recently Popular',
            'administrator',
        	'recently-popular',
            array(&$this, 'option_page'),
            ''
        );
    }

    /*
     * Generate the options page and handle submission.
     *
     * This form uses the self-redirect method to stop multiple form submissions on
     * page refresh. To be able to do that we have to stop Wordpress from sending its
     * header information, so we use the noheader query string parameter then strip it
     * back off when we've taken the submission and are redirecting back to the
     * settings page.
     */
    public function option_page() {
        global $wpdb;
        $table_name = $this->get_table_name();
        $hidden_field_name = 'submit_hidden_'.$this->table_suffix;

        // Check for form submission.
        if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && check_admin_referer('recently_popular_delete_counts', 'recently_popular_nonce')) {
            // Ensure they clicked the truncate button and checked the box saying
            // it was Ok to delete their data.
            if (isset($_POST['truncate']) && isset($_POST['delete_ok'])) {
                if ($_POST['interval_type'] == 'all') {
                    $wpdb->query("TRUNCATE TABLE `$table_name`;");
                }
                else {
                    $interval_length = $_POST['interval_length'];
                    $interval_type = $_POST['interval_type'];
                    $sql = "
                    	DELETE FROM `$table_name`
                        WHERE `ts` < (CURRENT_TIMESTAMP() - INTERVAL $interval_length $interval_type);
                    ";
                    $wpdb->query($sql);
                }
                $url = RecentlyPopularUtil::remove_query_string_item('noheader');
                $url = RecentlyPopularUtil::add_query_string_item('counts-deleted', 'true', $url);
                header("Location: $url");
                exit();
            }
        }
        if (isset($_GET['noheader'])) {
            $url = RecentlyPopularUtil::remove_query_string_item('noheader');
            header("Location: $url");
            exit();
        }
        if (isset($_GET['counts-deleted'])) {
            ?>
            <div class="updated"><p><strong><?php _e('Counts deleted.', 'recently-popular') ?></strong></p></div>
            <?php
        }

        // Get the date of the oldest count for display.
        $earliest_date = __('You have no counts in your database.', 'recently-popular');
        $res = $wpdb->get_row("SELECT `ts` FROM `$table_name` ORDER BY `ts` ASC LIMIT 1;");
        if ($res) {
            $earliest_date = sprintf(__('Your oldest count is from %s.', 'recently-popular'), date('Y-m-d', strtotime($res->ts)));
        }
        /*
        // This method is not calendar aware and needs help.
        $res = $wpdb->get_row("SELECT UNIX_TIMESTAMP(`ts`) as `ts` FROM $table ORDER BY `ts` ASC LIMIT 1");
        if ($res) {
            $earliest_date = 'Your oldest count is from approximately '.sectostr(time() - $res->ts).' ago.';
        }
        */

        ?>
        <div class="wrap">
            <h2><?php _e('Recently Popular Plugin Options', 'recently-popular') ?></h2>
        	<form name="form1" method="post" action="<?php echo RecentlyPopularUtil::add_query_string_item('noheader', 'true', RecentlyPopularUtil::remove_query_string_item('counts-deleted')) ?>">
                <?php wp_nonce_field('recently_popular_delete_counts', 'recently_popular_nonce'); ?>
                <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
                <!--p class="submit">
                    <input type="submit" name="Submit" value="Update Options" />
                </p>
                <hr/-->
                <h3><?php _e('Delete Counts', 'recently-popular') ?></h3>
                <p><?php _e('*** WARNING *** This cannot be undone!', 'recently-popular') ?></p><br/>
                <?php echo $earliest_date ?><br/><br/>
                <?php _e('Delete counts older than', 'recently-popular') ?> <input type="text" name="interval_length" value="0"/>
                <select name="interval_type">
                    <option value="HOUR"><?php _e('Hours', 'recently-popular') ?></option>
                    <option value="DAY"><?php _e('Days', 'recently-popular') ?></option>
                    <option value="WEEK"><?php _e('Weeks', 'recently-popular') ?></option>
                    <option value="MONTH"><?php _e('Months', 'recently-popular') ?></option>
                    <option value="YEAR"><?php _e('Years', 'recently-popular') ?></option>
                    <option value="all"><?php _e('--ALL--', 'recently-popular') ?></option>
                </select><br/><br/>
                <input type="checkbox" name="delete_ok" id="delete_ok" /><label for="delete_ok"><?php _e('Check here to confirm you wish to delete your post counts.', 'recently-popular') ?></label>
                <p class="submit">
                    <input type="submit" name="truncate" value="<?php _e('Delete Counts', 'recently-popular') ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    /*
     * Record that a post or page was viewed and the type of user that viewed it.
     */
    public function record_hit() {
        // Only record the view if it is a post or a page.  This keeps us from recording listings pages.
        if (is_single() || is_page()) {
    		global $wpdb, $user_ID, $post;
    		$table_name = $this->get_table_name();
    		$user_type = (empty($_COOKIE[USER_COOKIE]) && intval($user_ID) == 0) ? RecentlyPopularUserType::ANONYMOUS : RecentlyPopularUserType::REGISTERED;
    		$sql = "INSERT INTO `$table_name`(`user_type`, `post_id`) VALUES ($user_type, $post->ID);";
    		$wpdb->query($sql);
        }
    }

    /*
     * Get the counts to display.
     */
    public function get_counts($ops = array()) {
        global $wpdb;
        $table_name = $this->get_table_name();
        $o = wp_parse_args($ops, $this->data);
		$available_post_types = RecentlyPopularUtil::get_post_types(true);
		$post_types = array();

        // Establish the needed where clauses.
        $wc_user_type = '';
    	$wc_post_types = '';
    	$wc_categories = '';
    	$wc_post_id = '';
    	$join_categories = '';
    	$gb_categories = '';

        if ($o['user_type'] != RecentlyPopularUserType::ALL) {
    		$wc_user_type = " AND `rp`.`user_type` = $o[user_type] ";
    	}

		/**
		 * Backwards compatibility with 0.7.2
		 */
		if (isset($o['post_type'])) {
			switch ($o['post_type']) {
				case RecentlyPopularPostType::ALL :
					$o['post_types'] = array('post', 'page');
					break;

				case RecentlyPopularPostType::PAGES :
					$o['post_types'] = array('page');
					break;

				case RecentlyPopularPostType::POSTS :
					$o['post_types'] = array('post');
					break;
			}
		}

		// Check selected post types for availability
		foreach ($o['post_types'] as $post_type) {
			if (in_array($post_type, $available_post_types)) {
				$post_types[] = "`p`.`post_type` = '$post_type'";
			}
		}

		// Build post type clause
		if (count($post_types) > 0) {
			$wc_post_types = ' AND (' . implode(' OR ', $post_types) . ')';
		}

		if ($o['relative_time']) {
    		$o['interval_length'] = RecentlyPopularUtil::relative_timestamp($o['interval_length'], $o['interval_type']);
    		$o['interval_type'] = 'SECOND';
    	}

    	if (!empty($o['post_id'])) {
    		$o['post_id'] = strval($o['post_id']);
    		$wc_post_id = " AND `rp`.`postid` = '" . $o['post_id'] . "' ";
    	}

    	if ($o['enable_categories'] == 1) {
    		if (strlen($o['categories']) > 0) {
	    		// Define the where-clause (wc) and join this way so that we don't
	    		// join the term tables unless we need to.
	    		$o['categories'] = stripslashes($o['categories']);

	   			$wc_categories = " AND `tt`.`taxonomy` = 'category'
	   							   AND `t`.`name` IN ($o[categories]) ";

    		}
    		else {
   	        	$wc_categories = " AND `tt`.`taxonomy` = 'category' ";
    		}
    	}

    	// Using the sub-select speeds the query up about 15x, even when the term
    	// tables are joined for every query.  This allows us to expose more info
    	// for the template tags without harming performance.
    	$sql = "SELECT
    				`rp`.`hits` AS `hits`,
    				`rp`.`postid` AS `post_id`,
    				`p`.`post_title` AS `post_title`,
    				`p`.`post_excerpt` AS `post_excerpt`,
       				`p`.`post_date` AS `post_date`,
    				`p`.`post_type` AS `post_type`,
    				`u`.`display_name` AS `display_name`,
    				`u`.`user_url` AS `user_url`,
    				GROUP_CONCAT(DISTINCT `t`.`name` ORDER BY `t`.`name` SEPARATOR ', ') AS `category`
    			FROM (
                		SELECT
                			COUNT(`post_id`) AS `hits`,
                			MIN(`ts`) AS `ts`,
                			MIN(`user_type`) AS `user_type`,
                			MIN(`post_id`) AS `postid`
                		FROM 	`$table_name`
                		WHERE   `ts` > (CURRENT_TIMESTAMP() - INTERVAL $o[interval_length] $o[interval_type])
                		GROUP 	BY `post_id`
                		ORDER	BY `hits` DESC
                	 ) AS `rp`
    			LEFT JOIN `$wpdb->posts` AS `p` ON `rp`.`postid` = `p`.`ID`
    			LEFT JOIN `$wpdb->users` AS `u` ON `p`.`post_author` = `u`.`ID`
    			LEFT JOIN `$wpdb->term_relationships` AS `tr` ON `p`.`ID` = `tr`.`object_id`
    			LEFT JOIN `$wpdb->term_taxonomy` AS `tt` ON `tr`.`term_taxonomy_id` = `tt`.`term_taxonomy_id`
    			LEFT JOIN `$wpdb->terms` AS `t` ON `tt`.`term_id` = `t`.`term_id`
    			WHERE 1
                    $wc_user_type
                    $wc_post_types
                    $wc_categories
                    $wc_post_id
    			GROUP BY `rp`.`postid`
    			ORDER BY `rp`.`hits` DESC, `rp`.`ts` DESC
    			LIMIT $o[limit]
    	";

    	$most_viewed = $wpdb->get_results($sql);

    	if ($most_viewed) {
    		$loutput = new RecentlyPopularOutputParser($most_viewed, $o);

			echo $loutput->output;
    	}

    	if ($o['display']) { echo $output; }
    	else { return $output; }
    }

    /*
     * Handle all possible types of plugin activation.
     */
    public function activate() {
    	global $wpdb;
    	// Is this multisite and did the user click network activate?
    	$is_multisite = (function_exists('is_multisite') && is_multisite());
    	$is_networkwide = (isset($_GET['networkwide']) && $_GET['networkwide'] == 1);
    	if ($is_multisite && $is_networkwide) {
    	    // Get the current blog so we can return to it.
	        $current_blog_id = $wpdb->blogid;
	        // Get a list of all blogs.
	        $blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
	        foreach ($blog_ids as $blog_id) {
	            switch_to_blog($blog_id);
	            $this->create_table();
	        }
	        switch_to_blog($current_blog_id);
    	}
    	// Otherwise we're only working on one blog.
    	else {
    	    $this->create_table();
    	}
    }

    /*
     * Handle all possible types of plugin deactivation.
     */
    public function deactivate() {
    	global $wpdb;
    	// Is this multisite and did the user click network deactivate?
    	$is_multisite = (function_exists('is_multisite') && is_multisite());
    	$is_networkwide = (isset($_GET['networkwide']) && $_GET['networkwide'] == 1);
    	if ($is_multisite && $is_networkwide) {
    	    // Get the current blog so we can return to it.
	        $current_blog_id = $wpdb->blogid;
	        // Get a list of all blogs.
	        $blog_ids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
	        foreach ($blog_ids as $blog_id) {
	            switch_to_blog($blog_id);
	            $this->drop_table();
	        }
	        switch_to_blog($current_blog_id);
    	}
    	// Otherwise we're only working on one blog.
    	else {
    	    $this->drop_table();
    	}
    }

    /*
     * Watch network installs for new blogs being created.
     */
    public function create_blog($blog_id) {
        global $wpdb;
        if (is_plugin_active_for_network($this->short_path)) {
            $current_blog_id = $wpdb->blogid;
            switch_to_blog($blog_id);
            $this->create_table();
            switch_to_blog($current_blog_id);
        }
    }

    /*
     * Watch network installs for blogs being deleted.
     */
    public function delete_blog($blog_id) {
        global $wpdb;
        if (is_plugin_active_for_network($this->short_path)) {
            $current_blog_id = $wpdb->blogid;
            switch_to_blog($blog_id);
            $this->drop_table();
            switch_to_blog($current_blog_id);
        }
    }

    /*
     * Private database functions.
     */
    private function create_table() {
        global $wpdb;
        $table_name = $this->get_table_name();

        // Don't create it if it exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        	$sql = "CREATE TABLE `$table_name` (
                `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `user_type` tinyint(3) unsigned NOT NULL DEFAULT '1',
                `post_id` bigint(20) unsigned NOT NULL,
                KEY `index_all` (`ts`,`user_type`,`post_id`)
                );";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    private function drop_table() {
        global $wpdb;
        $table_name = $this->get_table_name();

        // Don't attempt to drop it unless it exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        	$sql = "DROP TABLE `$table_name`;";
        	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        	$wpdb->query($sql);
        }
    }
}

new RecentlyPopular();
