<?php 

	/*
		Plugin Name: ACF to Content
		Plugin URI: https://github.com/Hube2/acf-to-content
		GitHub Plugin URI: https://github.com/Hube2/acf-to-content
		Description: Add ACF fields to post_content for search
		Version: 1.1.0
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2
		
	*/
	
	// exit if accessed directly
	if (!defined('ABSPATH')) {
		exit;
	}
	
	require(dirname(__FILE__).'/field-settings.php');
	require(dirname(__FILE__).'/process-content.php');
	
	global $acf_to_post_content;
	$acf_to_post_content = new acf_to_post_content();
	
	class acf_to_post_content {
		
		// this field will hold content that needs to be added to the post_content
		// at is created so that after the post has been saved this update can happen
		// it will store multiple posts so that processing on any post can occur
		private $content = array();
		
		// should update be done
		// if nothing was saved by ACF then nothing should be updated in content
		private $do_updates = array();
		
		// this is an array of fucntion names that match
		// the macth values in $field_types => $field_type => 'handling'
		private $active_handlings = array('text');
		
		// this is a list of known field types 
		// that will be allowed
		// and how they will be treated
		// not all of the standard field types will allow
		// storing in content
		private $field_types = array(
			
			/* BASIC */
			'text' => array('handling' => 'text', 'filter_hook' => false),
			'textarea' => array('handling' => 'text', 'filter_hook' => false),
			//'number' => array('handling' => 'text', 'filter_hook' => false),
			//'range' => array('handling' => 'text', 'filter_hook' => false), // just a number field ?
			//'email' => array('handling' => 'text', 'filter_hook' => false),
			//'url' => array('handling' => 'text', 'filter_hook' => false),
			
			/* CONTENT */
			//'image' => array('handling' => '', 'filter_hook' => false),
			//'file' => array('handling' => '', 'filter_hook' => false),
			'wysiwyg' => array('handling' => 'text', 'filter_hook' => false),
			//'oembed' => array('handling' => '', 'filter_hook' => false),
			//'gallery' => array('handling' => '', 'filter_hook' => false),
			'medium_editor' => array('handling' => 'text', 'filter_hook' => false),
			
			/* CHOICE */
			//'select' => array('handling' => '', 'filter_hook' => false),
			//'checkbox' => array('handling' => '', 'filter_hook' => false),
			//'radio' => array('handling' => '', 'filter_hook' => false),
			//'button_group' => array('handling' => '', 'filter_hook' => false),
			//'true_false' => array('handling' => '', 'filter_hook' => false),
			
			/* RELATIONAL */
			//'link' => array('handling' => '', 'filter_hook' => false),
			//'post_object' => array('handling' => '', 'filter_hook' => false),
			//'page_link' => array('handling' => '', 'filter_hook' => false),
			//'relaitionship' => array('handling' => '', 'filter_hook' => false),
			//'taxonomy' => array('handling' => '', 'filter_hook' => false),
			//'user' => array('handling' => '', 'filter_hook' => false),
			
			/* JQUERY */
			//'google_map' => array('handling' => '', 'filter_hook' => false),
			//'date_picker' => array('handling' => 'date_time', 'filter_hook' => false),
			//'date_time_picker' => array('handling' => 'date_time', 'filter_hook' => false),
			//'time_picker' => array('handling' => 'date_time', 'filter_hook' => false),
			//'color_picker' => array('handling' => '', 'filter_hook' => false),
			
			
			// note that layout fields do not actually store values
			// this may indicate that sub fields will be processed
			//'message' => array('handling' => '', 'filter_hook' => false),
			//'accordion' => array('handling' => '', 'filter_hook' => false),
			//'accordion' => array('handling' => '', 'filter_hook' => false),
			//'tab' => array('handling' => '', 'filter_hook' => false),
			//'group' => array('handling' => '', 'filter_hook' => false),
			//'repeater' => array('handling' => 'repeater', 'filter_hook' => false),
			//'flexible_content' => array('handling' => 'flexible_content', 'filter_hook' => false),
			//'clone' => array('handling' => 'clone', 'filter_hook' => false)
		);
		
		public function __construct() {
			
			// run update value on every field
			add_action('acf/update_value', array($this, 'update_value'), 999999, 3);
			
			// allow filtering of the field types to copy to post_content
			add_filter('acf-to-content/field-types', array($this, 'get_field_types'), 1, 1);
			
			// after acf saves values, copy to post_content
			add_action('acf/save_post', array($this, 'save_post'), 999999);
			
			// remove added content before displaying on front end of site
			add_filter('the_content', array($this, 'remove_acf_content'), 1);
			
			// remove added content from standard WP content editor
			add_filter('the_editor_content', array($this, 'remove_acf_content'), 1);
			
			// WPAI actions
			add_action('pmxi_acf_custom_field', array($this, 'pmxi_acf_custom_field'), 999999, 3);
			add_action('pmxi_saved_post', array($this, 'save_post'), 999999, 1);
			
			// custom filters/actions for others to use
			add_action('acf_to_content/update_value', array($this, 'pmxi_acf_custom_field'), 999999, 3);
			add_action('acf_to_content/save_post', array($this, 'save_post'), 999999, 1);
			
		} // end public function __construct
		
		public function pmxi_acf_custom_field($value, $post_id, $field_name) {
			$values = array(
				'post_id' => $post_id,
				'field_name' => $field_name,
				'value' => $value
			);
			$field_object = get_field_object($field_name, $post_id);
			if ($field_object) {
				$value = $this->update_value($value, $post_id, $field_object);
			}
			return $value;
		} // end public function pmxi_acf_custom_field
		
		public function save_post($post_id) {
			// this function will add acf content to post content if it exists
			
			// test to see if acf values were updated
			if (!isset($this->do_updates[$post_id])) {
				// no updates for this post
				return;
			}
			$this->content[$post_id] = trim($this->content[$post_id]);
			
			$post = get_post($post_id);
			
			// remove any previous ACF fields from post content
			
			$post_content = $this->remove_acf_content($post->post_content);
			
			// if there is post content saved add it to the content
			if (!empty($this->content[$post_id])) {
				$post_content .= "\r\n".'<!-- START SSI ACF TO CONTENT -->'."\r\n".
				                 '<div style="display:none;">'.$this->content[$post_id].'</div>'."\r\n".
				                 '<!-- END SSI ACF TO CONTENT -->';
				
			}
			
			$post->post_content = $post_content;
			
			// remove this action
			remove_filter('acf/save_post', array($this, 'save_post'), 999999);
			
			// update post
			wp_update_post($post);
			
			// re-add this action
			add_action('acf/save_post', array($this, 'save_post'), 999999);
			
		} // end public function save_post
		
		public function update_value($value, $post_id, $field) {
			// this function is called every time ACF updates a field value
			
			// this only works on posts
			if (!is_numeric($post_id)) {
				return $value;
			}
			
			// never allow password fields
			if ($field['type'] == 'password') {
				return $value;
			}
			
			// allow 3rd party filtering of any field
			$to_content = false;
			// all fields
			$to_content = apply_filters('acf-to-content/custom-process', $to_content, $value, $post_id, $field);
			// by field type
			$to_content = apply_filters('acf-to-content/custom-process/type='.$field['type'], $to_content, $value, $post_id, $field);
			// by field name
			$to_content = apply_filters('acf-to-content/custom-process/name='.$field['name'], $to_content, $value, $post_id, $field);
			// by field key
			$to_content = apply_filters('acf-to-content/custom-process/key='.$field['key'], $to_content, $value, $post_id, $field);
			if ($to_content) {
				// filtered by a 3rd party filter
				// set content and skip built in processing
				$this->content[$post_id] .= ' '.$to_content;
				$this->do_updates[$post_id] = $post_id;
				return $value;
			}
			
			
			if (!isset($field['to_content']) || !$field['to_content']) {
				// this field is not being added to content setting
				return $value;
			}
			
			// process content and add it to $this->content[$post_id]
			
			// get field types that allow processing and the type of processing they use
			$filtered_types = apply_filters('acf-to-content/field-types', array());
			
			if (isset($filtered_types[$field['type']])) {
				$handling = $filtered_types[$field['type']]['handling'];
			}
			if (!$handling) {
				// no handling set for this field type
				return $value;
			}
			if (!isset($this->content[$post_id])) {
				$this->content[$post_id] = '';
			}
			
			$this->content[$post_id] .= ' '.apply_filters('acf-to-content/process', $value, $post_id, $field, $handling);
			$this->do_updates[$post_id] = $post_id;
			
			return $value;
		} // end public function update_value
		
		public function get_field_types($value=array()) {
			return $this->field_types;
		} // end public function get_field_types
		
		public function get_active_handlings() {
			return $this->active_handlings;
		} // end public function get_active_handlings
		
		public function remove_acf_content($content) {
			return trim(preg_replace('#<!-- START SSI ACF TO CONTENT.*END SSI ACF TO CONTENT -->#is', 
															'', $content));
		} // end public function remove_content
		
	} // end class acf_to_post_content
	
?>