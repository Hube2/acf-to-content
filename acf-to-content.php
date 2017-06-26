<?php 

	/*
		Plugin Name: ACF to Content
		Plugin URI: https://github.com/Hube2/acf-to-content
		GitHub Plugin URI: https://github.com/Hube2/acf-to-content
		Description: Add ACF fields to post_content for search
		Version: 0.0.2
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
		
		private $active_handlings = array('text');
		
		// this is a list of known field types 
		// that will be allowed
		// and how they will be treated
		// not all of the standard field types will allow
		// storing in content
		private $field_types = array(
			'text' => array('handling' => 'text'),
			'textarea' => array('handling' => 'text'),
			//'number' => array('handling' => 'text'),
			//'email' => array('handling' => 'text'),
			//'url' => array('handling' => 'text'),
			'wysiwyg' => array('handling' => 'text'),
			//'oembed' => array('handling' => ''),
			//'image' => array('handling' => 'image'),
			//'file' => array('handling' => 'file'),
			//'gallery' => array('handling' => 'image'),
			//'select' => array('handling' => 'choice'),
			//'checkbox' => array('handling' => 'choice'),
			//'radio' => array('handling' => 'choice'),
			//'true_false' => array('handling' => ''),
			//'post_object' => array('handling' => 'relationship'),
			//'page_link' => array('handling' => 'page_link'),
			//'relaitionship' => array('handling' => 'relationship'),
			//'taxonomy' => array('handling' => 'taxonomy'),
			//'user' => array('handling' => 'user'),
			//'google_map' => array('handling' => 'google_map'),
			//'date_picker' => array('handling' => 'date_time'),
			//'date_time_picker' => array('handling' => 'date_time'),
			//'time_picker' => array('handling' => 'date_time'),
			//'color_picker' => array('handling' => ''),
			//'message' => array('handling' => ''),
			//'tab' => array('handling' => ''),
			
			
			// note that layout fields do not actually store values
			// this may indicate that sub fields will be processed
			//'repeater' => array('handling' => 'repeater'),
			//'flexible_content' => array('handling' => 'flexible_content'),
			//'clone' => array('handling' => 'clone')
		);
		
		// list of fields to copy 
		// [meta_key(field name)] => field_key
		private $field_list = array();
		
		// field settings for each field from acf
		// [field_key] => field settings
		private $acf_fields = array();
		
		// field content to copy
		// [meta_key(field name)] => content
		private $field_content = array();
		
		// field names to get content for
		private $field_name_list = array();
		
		
		public function __construct() {
			add_filter('acf/update_field', array($this, 'update_field'), 20);
			add_action('acf/delete_field', array($this, 'delete_field'));
			
			add_filter('acf-to-content/field-types', array($this, 'get_field_types'), 1, 1);
			
			add_action('acf/save_post', array($this, 'pre_save_delete'), 1);
			add_action('acf/save_post', array($this, 'save_post'), 20);
			
			add_filter('the_content', array($this, 'remove_acf_content'), 1);
			add_filter('the_editor_content', array($this, 'remove_acf_content'), 1);
		} // end public function __construct
		
		public function get_active_handlings() {
			return $this->active_handlings;
		} // end public function get_active_handlings
		
		public function get_field_types($value=array()) {
			return $this->field_types;
		} // end public function get_field_types
		
		public function delete_field($field) {
			$field_key_list = get_option('acf_to_content_key_list', array());
			$key = $field['key'];
			if (isset($field_key_list[$key])) {
				unset($field_key_list[$key]);
				update_option('acf_to_content_key_list', $field_key_list, true);
			}
		} // end public function delete_field
		
		public function update_field($field) {
			//echo '<pre>'; print_r($field); echo '</pre>';
			$field_key_list = get_option('acf_to_content_key_list', array());
			$change = false;
			if (isset($field['to_content'])) {
				$post_id = $field['ID'];
				//$value = $field['to_content'];
				//$meta_key = 'acf_to_content_setting';
				
				// flag this field with value
				//update_post_meta($post_id, $meta_key, $value);
				if ($field['to_content'] && !isset($field_key_list[$field['key']])) {
					$change = true;
					$field_key_list[$field['key']] = $field['key'];
				} elseif (!$field['to_content'] && isset($field_key_list[$field['key']])) {
					$change = true;
					unset($field_key_list[$field['key']]);
				}
				
				// set field key to make it easier to find
				//$value = $field['key'];
				//$meta_key = 'acf_to_content_key';
				//update_post_meta($post_id, $meta_key, $value);
				
			} elseif (isset($field_key_list[$field['key']])) {
				$change = true;
				unset($field_key_list[$field['key']]);
			}
			if ($change) {
				update_option('acf_to_content_key_list', $field_key_list, true);
			}
			return $field;
		} // end public function update_field
		
		public function pre_save_delete($post_id) {
			
			if ($_POST['_acfchanged'] == 0 || !is_numeric($post_id)) {
				// no acf field change, no need to run this
				return;
			}
			
			// in order to make sure that content is correct
			// content needs to be deleted before ACF update
			// any fields that may be removed because of repeaters
			// or conditonal logic must be removed
			// the best way to accomplish this is to just
			// delete all exsiting content for fields that need to
			// be moved to content
			
			// just in case this happens more than once, clear any previous results
			$this->acf_field = array();
			$this->field_list = array();
			$this->field_content = array();
			$this->field_name_list = array();
			
			// get filtered field types
			$field_types = apply_filters('acf-to-content/field-types', array());
			
			$field_key_list = get_option('acf_to_content_key_list', array());
			
			if (empty($field_key_list)) {
				// no fields to copy
				return;
			}
			// store list of all field keys and field settings from above query
			$field_keys = $this->get_field_keys();
			
			if (!count($field_keys)) {
				return;
			}
			
			$delete_keys = $this->get_delete_keys($post_id, $field_keys);
			
			if (!$delete_keys) {
				return;
			}
			
			global $wpdb;
			$delete_keys = $wpdb->_escape($delete_keys);
			$query = 'DELETE FROM '.$wpdb->postmeta.'
								WHERE post_id = "'.$post_id.'"
									AND meta_key IN ("'.implode('","', $delete_keys).'")';
			$wpdb->query($query);
			clean_post_cache($post_id);
			
		} // end public function pre_save_delete
		
		private function get_field_keys() {
			
			$field_keys = array();
			
			$field_key_list = get_option('acf_to_content_key_list', array());
			
			if (empty($field_key_list)) {
				// no fields to copy
				return $field_keys;
			}
			
			foreach ($field_key_list as $field_key) {
				//echo $field_id;
				$field = acf_get_field($field_key);
				
				if (!$field) {
					continue;
				}
				
				$field_keys[] = $field['key'];
				$this->acf_fields[$field['key']] = $field;
				wp_cache_delete('get_field/key='.$field['key'], 'acf');
				wp_cache_delete('get_field/ID='.$field['ID'], 'acf');
			}
			
			return $field_keys;
			
		} // end private function get_field_keys
		
		private function get_delete_keys($post_id, $field_keys) {
			global $wpdb;
			$delete_keys = array();
			// get list of meta keys matching field keys
			$field_keys = $wpdb->_escape($field_keys);
			$query = 'SELECT meta_key, meta_value
								FROM '.$wpdb->postmeta.'
								WHERE post_id = "'.$post_id.'"
									AND meta_value IN ("'.implode('","', $field_keys).'")';
			$fields = $wpdb->get_results($query, 'ARRAY_A');
			
			if (!count($fields)) {
				return $delete_keys;
			}
			
			// store fields in field list and delete content
			foreach ($fields as $field) {
				$delete_keys[] = $field['meta_key'];
				$delete_keys[] = substr($field['meta_key'], 1);
				$this->field_list[substr($field['meta_key'], 1)] = $field['meta_value'];
				$this->field_name_list[] = substr($field['meta_key'], 1);
			}
			return $delete_keys;
		} // end private function get_delete_keys
		
		public function save_post($post_id) {
			
			if (!is_numeric($post_id)) {
				return;
			}
			
			// this needs to run every time because acf content
			// is removed from the default content editor before
			// it is displayed for editing
			if ($_POST['_acfchanged'] == 0) {
				// no acf field change
				// but nothing was retreaved during the pre save phase
				// so we need to populate the needed data
				$field_keys = $this->get_field_keys();
				// call delete keys but do not use
				$delete_keys = $this->get_delete_keys($post_id, $field_keys);
			}
			// this is the function that gets all of the acf field content
			// and adds it to post_content
			$content = $this->get_content($post_id);
			
			$post = get_post($post_id);
			
			$post_content = $this->remove_acf_content($post->post_content);
			$post_content .= "\r\n".'<!-- START SSI ACF TO CONTENT -->'."\r\n".
											 '<div style="display:none;">'.$content.'</div>'."\r\n".
											 '<!-- END SSI ACF TO CONTENT -->';
			$post->post_content = $post_content;
			
			
			// remove filters to prevent infinite loop
			remove_filter('acf/save_post', array($this, 'pre_save_delete'), 1);
			remove_filter('acf/save_post', array($this, 'save_post'), 20);
			
			// update post
			wp_update_post($post);
			
			// add filters back in
			add_action('acf/save_post', array($this, 'pre_save_delete'), 1);
			add_action('acf/save_post', array($this, 'save_post'), 20);
			
		} // end public function save_post
		
		public function remove_acf_content($content) {
			return trim(preg_replace('#<!-- START SSI ACF TO CONTENT.*END SSI ACF TO CONTENT -->#is', 
															'', $content));
		} // end public function remove_content
		
		private function get_content($post_id) {
			$content = '';
			if (!count($this->field_name_list)) {
				return $content;
			}
			global $wpdb;
			$fields = $wpdb->_escape($this->field_name_list);
			$query = 'SELECT meta_key, meta_value 
								FROM '.$wpdb->postmeta.'
								WHERE post_id = "'.$post_id.'"
									AND meta_key IN ("'.implode('","', $fields).'")';
			$results = $wpdb->get_results($query, 'ARRAY_A');
			
			if (!count($results)) {
				return $content;
			}
			
			$filtered_types = apply_filters('acf-to-content/field-types', array());
			
			foreach ($results as $result) {
				$field_name = $result['meta_key'];
				$value = $result['meta_value'];
				$field_key = $this->field_list[$field_name];
				$field_type = $this->acf_fields[$field_key]['type'];
				$handling = false;
				if (isset($filtered_types[$field_type])) {
					$handling = $filtered_types[$field_type]['handling'];
				}
				if (!$handling) {
					// no handling set for this field type
					continue;
				}
				$content .= ' '.apply_filters('acf-to-content/process', $value, $handling); 
			}
			return $content;
		} // end private function get_content
		
	} // end class acf_to_post_content
	
?>