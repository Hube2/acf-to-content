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
			}
			
			return wp_strip_all_tags($value, true);
		} // end public function process
		
		private function text($value, $post_id, $field) {
			// not doing anything now, may want to do something later, but it's just a text value anyway
			// may want to strip html at some point, but I don't know
			return $value;
		} // end private function text
		
		private function taxonomy($value, $post_id, $field) {
			if (empty($value)) {
				return $value;
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