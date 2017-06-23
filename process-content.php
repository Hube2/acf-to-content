<?php 
	
	// exit if accessed directly
	if (!defined('ABSPATH')) {
		exit;
	}
	
	new acf_to_post_content_process();
	
	class acf_to_post_content_process {
		
		public function __construct() {
		
			add_filter('acf-to-content/process', array($this, 'process'), 1, 2);
		
		} // end public function __construct
		
		public function process($value, $handler) {
			
			global $acf_to_post_content;
			
			$active_handlers = $acf_to_post_content->get_active_handlings();
			if (!in_array($handler, $active_handlers)) {
				return $value;
			}
			$value = $this->{$handler}($value);
			return $value;
		} // end public function process
		
		private function text($value) {
			// not doing anything now, may want to do something later
			return $value;
		} // end private function text
		
	} // end class acf_to_post_content_process
	
	
	
?>