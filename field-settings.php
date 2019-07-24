<?php 
	
	// exit if accessed directly
	if (!defined('ABSPATH')) {
		exit;
	}
	
	new acf_to_post_content_field_settings();
	
	class acf_to_post_content_field_settings {
		
		public function __construct() {
			add_action('acf/init', array($this, 'add_actions'), 20);
		} // end public function __construct
		
		public function add_actions() {
			$acf_types = acf_get_field_types();
			$filtered_types = apply_filters('acf_to_content/field_types', array());
			// active handlings
			global $acf_to_post_content;
			$active_handlers = $acf_to_post_content->get_active_handlings();
			foreach ($acf_types as $type => $settings) {
				if (isset($filtered_types[$type]) && 
						in_array($filtered_types[$type]['handling'], $active_handlers)) {
					$function = $filtered_types[$type]['handling'];
					add_action('acf/render_field_settings/type='.$type, array($this, $function), 1);
				} // end if isset type
			} // end foreach type
		} // end public function add_actions
		
		public function all($field) {
			// settings added to all field typs
			$args = array(
				'type' => 'true_false',
				'label' => 'To Content',
				'name' => 'to_content',
				'message'	=> 'Save the value of this field to post_content?',
				'required' => 0,
				'default_value' => 0,
				'ui' => 1,
				'ui_on_text' => '',
				'ui_off_text' => '',
				'wrapper' => array(
					'width' => '',
					'class' => 'acf-to-content',
					'id' => ''
				)
			);
			acf_render_field_setting($field, $args, false);
		} // end public function all
		
		public function text($field) {
			$this->all($field);
		} // end public function text
		
		public function taxonomy($field) {
			$this->all($field);
			$args = array(
				'type' => 'checkbox',
				'label' => '',
				'instructions' => '',
				'name' => 'to_content_format',
				'_append' => 'to_content',
				'choices' => array(
					'name' => 'Name',
					'slug' => 'Slug',
					'description' => 'Description'
				),
				'default_value' => 'name',
				'layout' => 'horizontal',
				'required' => 0,
				'allow_null' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => 'acf-to-content-format',
					'id' => ''
				)
			);
			acf_render_field_setting($field, $args, false);
		} // end public function taxonomy
		
		public function choice($field) {
			$this->all($field);
			$args = array(
				'type' => 'radio',
				'label' => '',
				'instructions' => '',
				'name' => 'to_content_format',
				'_append' => 'to_content',
				'choices' => array(
					'label' => 'Label',
					'value' => 'Value',
					'both' => 'Both'
				),
				'default_value' => 'label',
				'layout' => 'horizontal',
				'required' => 0,
				'allow_null' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => 'acf-to-content-format',
					'id' => ''
				)
			);
			acf_render_field_setting($field, $args, false);
		} // end public function choice
		
	} // end class acf_to_post_content_field_settings
	
?>