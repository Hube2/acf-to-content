<?php 
	
	// exit if accessed directly
	if (!defined('ABSPATH')) {
		exit;
	}
	
	new acf_to_post_content_process();
	
	class acf_to_post_content_process {
		
		public function __construct() {
			add_filter('acf_to_content/process', array($this, 'process'), 10, 4);
		} // end public function __construct
		
		public function process($value, $post_id, $field, $handler) {
			global $acf_to_post_content;
			$active_handlers = $acf_to_post_content->get_active_handlings();
			if (in_array($handler, $active_handlers) && method_exists($this, $handler)) {
				$value = $this->{$handler}($value, $post_id, $field);
			} else {
				$value = $this->stringify($value);
			}
			return wp_strip_all_tags($value, true);
		} // end public function process
		
		private function stringify($input) {
			$return = '';
			if (is_array($input) || is_object($input)) {
				$list = array();
				foreach ($input as $value) {
					$list[] = $this->stringify($value);
				}
				if (count($list)) {
					$return = implode(' ', $list);
				}
			} else {
				$return = $input;
			}
			return $return;
		} // end private function stringify
		
		private function text($value, $post_id, $field) {
			// run all shortcodes
			$value = do_shortcode($value);
			// remove all html
			$value = wp_strip_all_tags($value, true);
			return $value;
		} // end private function text
		
		private function taxonomy($value, $post_id, $field) {
			if (empty($value)) {
				return '';
			}
			$value = acf_get_array($value);
			$args = array(
				'taxonomy'		=> $field['taxonomy'],
				'include'		=> $value,
				'hide_empty'	=> false
			);
			$terms = acf_get_terms($args);
			$return = '';
			$items = array();
			$format = array('name');
			if (!empty($field['to_content_format'])) {
				$format = $field['to_content_format'];
			}
			if (count($terms)) {
				foreach ($terms as $term) {
					foreach ($format as $what) {
						if (!empty($term->{$what})) {
							$items[] = $term->{$what};
						}
					} // end foreach format
				} // end foreach terms
			} // end if terms
			if (count($items)) {
				$return = implode(' ', $items);
			}
			return $return;
		} // end private function taxonomy
		
		private function post_relationship($value, $post_id, $field) {
			if (empty($value)) {
				return '';
			}
			$posts = acf_get_array($value);
			$return = '';
			$items = array();
			$format = array('post_title');
			if (!empty($field['to_content_format'])) {
				$format = $field['to_content_format'];
			}
			if (count($posts)) {
				foreach ($posts as $post_id) {
					$post = get_post($post_id);
					foreach ($format as $what) {
						switch ($what) {
							case 'post_content':
								$post_content = apply_filters('acf_to_content/remove_acf_content', $post->post_content);
								if (!empty($post_content)) {
									$items[] = $post_content;
								}
								break;
							case 'acf_content':
								$acf_content = apply_filters('acf_to_content/get_acf_content', $post->post_content);
								if (!empty($post_content)) {
									$items[] = $post_content;
								}
								break;
							case 'post_title':
							default: 
								if (!empty($post->post_title)) {
									$items[] = $post->{$what};
								}
								break;
						} // end switch
					} // end foreach format
				} // end foreach post
			} // end if posts
			if (count($items)) {
				$return = implode(' ', $items);
			}
			return $return;
		} // end private function post_relationship
		
		private function choice($value, $post_id, $field) {
			if (empty($value) || !isset($field['choices']) || !count($field['choices'])) {
				return $value;
			}
			$values = $value;
			if (!is_array($values)) {
				$values = array($values);
			}
			$choices = $field['choices'];
			$format = 'label';
			if (isset($field['to_content_format'])) {
				$format = $field['to_content_format'];
			}
			$return = '';
			$items = array();
			foreach ($choices as $value => $label) {
				if (in_array($value, $values)) {
					if ($format == 'label' || $format == 'both') {
						$items[] =  $label;
					}
					if ($format == 'value' || $format == 'both') {
						$items[] = $value;
					}
				} // end if choice is in values
			} // end foreach choice
			if (count($items)) {
				$return = implode(' ', $items);
			}
			return $return;
		} // end private function choice
		
	} // end class acf_to_post_content_process
	
?>