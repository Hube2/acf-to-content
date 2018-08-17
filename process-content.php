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
			if (!in_array($handler, $active_handlers)) {
				return $value;
			}
			$to_content = $value;
			if (method_exists($this, $handler)) {
				$value = $this->{$handler}($value);
			} else {
				
			}
			return $value;
		} // end public function process
		
		private function text($value) {
			// not doing anything now, may want to do something later
			return $value;
		} // end private function text
		
	} // end class acf_to_post_content_process
	
	
	
?>