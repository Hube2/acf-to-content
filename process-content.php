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
			
			$to_content = false;
			
			$active_handlers = $acf_to_post_content->get_active_handlings();
			if (in_array($handler, $active_handlers) && method_exists($this, $handler)) {
				$value = $this->{$handler}($value);
			}
			// for future use, planning how to add more filtering here
			if ($to_content) {
				$value = $to_content;
			}
			return $value;
		} // end public function process
		
		private function text($value) {
			// not doing anything now, may want to do something later, but it's just a text value anyway
			return $value;
		} // end private function text
		
	} // end class acf_to_post_content_process
	
	
	
?>