<?php
// Copyright (c) 2008 Eric Biven
// Released under the FreeBSD license:
// http://www.freebsd.org/copyright/freebsd-license.html
//
// For Recently Popular 0.7.2

/**
 * @deprecated since 0.4.8.1
 */
function get_recently_popular (
				$interval_length = '',
				$interval_type = '',
				$limit = '',
				$user_type = '',
				$post_type = '',
				$output_format = '',
				$categories = '',
				$date_format = '',
				$display = '' ) {

	$ops = array (
		'interval_length' => $interval_length,
		'interval_type' => $interval_type,
		'limit' => $limit,
		'user_type' => $user_type,
		'post_type' => $post_type,
		'output_format' => $output_format,
		'categories' => $categories,
		'date_format' => $date_format,
		'display' => $display
	);

	foreach ($ops as $key => $value) {
		if (!is_int($value) && empty($value)) { unset ($ops[$key]); }
	}

	return get_recently_popular2($ops);
}

/**
 * @since 0.4.8.1
 * @deprecated since 0.7
 */
function get_recently_popular2 ($ops = "") {
    if (is_string($ops)) {
		parse_str($ops, $o);
	}
	else {
		$o = $ops;
	}

	$rp = new RecentlyPopular();
	$rp->get_counts($o);
}

class RecentlyPopularUtil {

    public static $defaults = array(
        'categories' => '',
        'date_format' => 'Y-m-d',
        'default_thumbnail_url' => '',
        'display' => true,
        'enable_categories' => false,
    	'interval_length' => 1,
        'interval_type' => 'MONTH',
        'limit' => 10,
        'max_length' => 0,
        'max_excerpt_length' => 0,
        'output_format' => '<a href="%post_url%">%post_title%</a>',
        'post_types' => array('0'),
        'relative_time' => 0,
    	'title' => 'Recently Popular',
        'user_type' => RecentlyPopularUserType::ANONYMOUS,
    );

    public static function relative_timestamp($length, $type) {
    	$length--;
    	switch ( $type ) {
    		case 'HOUR' :
    			$time_start = mktime(date("H")-$length, 0, 0, date("m"), date("d"), date("Y"));
    			break;

    		case 'DAY' :
    			$time_start = mktime(0, 0, 0, date("m"), date("d")-$length, date("Y"));
    			break;

    		case 'WEEK' :
    			$length = ($length * 7 + date ("w") );
    			$time_start = mktime(0, 0, 0, date("m"), date("d")-$length, date("Y"));
    			break;

    		case 'MONTH' :
    			$time_start = mktime(0, 0, 0, date("m")-$length, 1, date("Y"));
    			break;

    		case 'YEAR' :
    			$time_start = mktime(0, 0, 0, 1, 1, date("Y")-$length);
    			break;

    		default :
    			$time_start = time();
    			break;

    	}

    	$timeframe = time() - $time_start;
    	return $timeframe;
    }

    /**
     * Truncate a string and add elipses if required.
     */
    public static function truncate($string, $length = 30, $tail = "...") {
    	if ($length == 0) { return $string; }
    	$string = trim($string);
    	$txtl = strlen($string);
    	if($txtl > $length) {
    		for($i=1; $string[$length-$i]!=" "; $i++) {
    			if($i == $length) {
    				return substr($string, 0, $length).$tail;
    			}
    		}
    		for(; $string[$length - $i]=="," || $string[$length - $i]=="." || $string[$length - $i]==" "; $i++) {;}
    		$string = substr($string, 0, $length - $i + 1).$tail;
    	}
    	return $string;
    }

    // Credit to http://www.addedbytes.com/lab/php-querystring-functions/,
    // with modifications.
    public static function add_query_string_item($key, $value, $url = '') {
        // If the user didn't give us an url use the current one.
        $url = (!isset($url) || strlen($url) ==  0) ? $_SERVER['REQUEST_URI'] : $url;
        $url = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        if (strpos($url, '?') === false) {
            return $url . '?' . $key . '=' . $value;
        } else {
            return $url . '&' . $key . '=' . $value;
        }
    }

    // Credit to http://www.addedbytes.com/lab/php-querystring-functions/,
    // with modifications.
    public static function remove_query_string_item($key, $url = '') {
        // If the user didn't give us an url use the current one.
        $url = (!isset($url) || strlen($url) ==  0) ? $_SERVER['REQUEST_URI'] : $url;
        $url = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        return $url;
    }

	/**
	 * Retrieve all queryable and public post types
	 *
	 * @since 0.?
	 *
	 * @param boolean $only_names Whether to retrieve only post types' names or not
	 */
	public static function get_post_types($only_names = false) {
		$args = array(
			'public' => true
		);

		return get_post_types($args, $only_names ? 'names' : 'objects');
	}

}

class RecentlyPopularOutputParser {
	private $options;
	private $post;
	public $output;

	function __construct($posts, $options) {
		$this->options = $options;

		foreach ($posts as $post) {
			$this->post = $post;

			$this->output .= "    <li>";
			$this->output .= $this->parse();
			$this->output .= "</li>\n";
		}
	}

	/**
	 * Parse all the tags from a string
	 *
	 * @since 0.?
	 *
	 * @param string $output String to parse
	 */
	private function parse($output = '') {
		$output = empty($output) ? $this->options['output_format'] : $output;
		return preg_replace_callback('/%(.*?)(\[(.*?)\])?%/', array($this, 'replace_tags'), $output);
	}

	/**
	 * Retrieve the ID for the featured image or the first image attachment of a given post.
	 * Needs WP >2.9.
	 *
	 * @since 0.?
	 *
	 * @param integer $post_id The post ID to get the thumbnail from
	 */
	private function get_thumbnail_id($post_id) {
		if (function_exists('has_post_thumbnail') && has_post_thumbnail($post_id)) {
			$id = get_post_thumbnail_id($post_id);
		}
		else {
			$images = get_children('post_type=attachment&post_mime_type=image&showposts=1&post_parent='.$post_id);
			$image = reset($images);
			$id = $image->ID;
		}

		return $id;
	}

	/**
	 * Replace all tags within output string
	 */
	private function replace_tags(array $matches) {
		$tag = $matches[1];
		$args = explode(';', $matches[3]);

		switch ($tag) {
			case 'thumbnail_url' :
			case 'post_thumbnail' :
				/*
				 * $args = array(
				 *     0 => size, (e.g. '120,90', 'large')
				 *     1 => parameter string (e.g. 'title=Motherboard&class=product')
				 * )
				 */

				$image_size = strpos($args[0], ',') !== false ? explode(',', $args[0]) : $args[0];
				$image_id = $this->get_thumbnail_id($this->post->post_id);

				$image_args = wp_parse_args($this->parse($args[1]));

				if ($tag == 'thumbnail_url') {
					$image = wp_get_attachment_image_src($image_id, $image_size, null, $image_args);
					return $image[0];
				}
				else
					return wp_get_attachment_image($image_id, $image_size, null, $image_args);
				break;

			case 'post_meta' :
				/*
				 * $args = array(
				 *     0 => meta_key
				 * )
				 */
				return get_post_meta($this->post->post_id, $args[0], true);
				break;

			case 'post_excerpt' :
				return ($this->options['max_excerpt_length'] > 0) ? RecentlyPopularUtil::truncate($this->post->post_excerpt, $this->options['max_excerpt_length']) : $this->post->post_excerpt;
				break;

			case 'post_url' :
				return get_permalink($this->post->post_id);
				break;

			case 'post_title' :
				return $this->post->post_title;
				break;

			case 'hits' :
				return $this->post->hits;
				break;

			case 'display_name' :
			case 'author' :
				return $this->post->display_name;
				break;

			case 'user_url' :
				return $this->post->user_url;
				break;

			case 'publish_date' :
				return date(($args[0] ? $args[0] : $this->options['date_format']), strtotime($this->post->post_date));
				break;

			case 'category' :
				return $this->post->category;
				break;
		}
		return;
	}
}

class RecentlyPopularUserType {
    // Types of users for recorded page views.
    const ALL = 0;
    const ANONYMOUS = 1;
    const REGISTERED = 2;
}

class RecentlyPopularPostType {
    // Types of posts
    const ALL = 0;
    const PAGES = 1;
    const POSTS = 2;
}
