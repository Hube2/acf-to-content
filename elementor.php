<?php 
	
	// exit if accessed directly
	if (!defined('ABSPATH')) {
		exit;
	}
	
	new acf_to_post_content_elementor();
	
	class acf_to_post_content_elementor {
		
		public function __construct() {
			
			add_action('save_post', array($this, 'save_post'), 9999999, 3);
			
			add_action('acf_to_content/elementor/remove_action', array($this, 'remove_action'));
			add_action('acf_to_content/elementor/add_action', array($this, 'add_action'));
			
		} // end public function __construct
		
		public function remove_action() {
			remove_filter('save_post', array($this, 'save_post'), 9999999);
		} // end public function remove_action
		
		public function add_action() {
			add_action('save_post', array($this, 'save_post'), 9999999, 3);
		} // end public function add_action
		
		public function save_post($post_id, $post, $update) {
			if (did_action('acf/save_post')) {
				return;
			}
			do_action('acf_to_content/save_post/from_meta', $post_id);
		} // end public function save_post
		
	} // end class acf_to_post_content_elementor