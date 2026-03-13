<?php
namespace MBBParser\Unparsers;

class Field extends Base {
	/**
	 * Allow these settings to be empty.
	 * @var array
	 */
	protected $empty_keys = [ 'save_field' ];

	private $choice_types = [ 'select', 'radio', 'checkbox_list', 'select_advanced', 'button_group', 'image_select', 'autocomplete' ];

	/**
	 * Known field keys handled natively by the Builder UI.
	 * Any other scalar keys will be moved to custom_settings during unparse.
	 * @var array
	 */
	private static $known_keys = [
		// Core field settings (all field types)
		'id',
		'_id',
		'type',
		'name',
		'std',
		'placeholder',
		'save_field',
		'_state',
		'field_name',
		'label_description',
		'desc',
		'columns',
		'before',
		'after',
		'class',
		'required',
		'disabled',
		'readonly',
		'hide_from_rest',
		'hide_from_front',
		'sanitize_callback',
		'attributes',
		'input_attributes',
		'multiple',
		// Text and textarea fields
		'size',
		'maxlength',
		'minlength',
		'pattern',
		'autocomplete',
		'autofocus',
		'prepend',
		'append',
		'datalist',
		'datalist_choices',
		'textarea_size',
		// Number and range fields
		'min',
		'max',
		'step',
		'minmax',
		// Choice fields (select, radio, checkbox, button group, image select)
		'options',
		'std',
		'inline',
		'select_all_none',
		'flatten',
		'field_type',
		// Select advanced
		'js_options',
		// File and image upload fields
		'max_file_uploads',
		'max_file_size',
		'mime_type',
		'force_delete',
		'image_size',
		'add_to',
		'max_status',
		'upload_dir',
		'unique_filename_callback',
		// Date and time fields
		'timestamp',
		'format',
		'save_format',
		// Color picker
		'alpha_channel',
		// Wysiwyg editor
		'raw',
		'height',
		// Switch field
		'style',
		'on_label',
		'off_label',
		// Slider field
		'prefix',
		'suffix',
		// Map and OSM fields
		'api_key',
		'address_field',
		'language',
		'region',
		'marker_draggable',
		// Icon field
		'icon_set',
		'icon_file',
		'icon_dir',
		'icon_css',
		// Object fields (taxonomy, post, user)
		'taxonomy',
		'post_type',
		'query_args',
		'ajax',
		'add_new',
		'remove_default',
		'parent',
		'display_field',
		// Block editor field
		'allowed_blocks',
		// Group field (Meta Box Group extension)
		'fields',
		'collapsible',
		'default_state',
		'save_state',
		'group_title',
		// Key-value field (sub-type of group)
		'placeholder_key',
		'placeholder_value',
		// Clone extension (Meta Box Group)
		'clone',
		'sort_clone',
		'clone_default',
		'clone_as_multiple',
		'min_clone',
		'max_clone',
		'add_button',
		'clone_empty_start',
		// Meta Box Builder extensions
		// Text limiter
		'text_limiter',
		'limit',
		'limit_type',
		// Conditional logic
		'conditional_logic',
		'visible',
		'hidden',
		// Tabs
		'tab',
		// Validation
		'validation',
		// Tooltips
		'tooltip',
		// Admin columns
		'admin_columns',
		// Custom settings container
		'custom_settings',
		// Internal use only
		'_callback',
	];

	/**
	 * This is revert of parse method. While parse method converts to the minimal format,
	 * this method converts back to the original format.
	 *
	 * Used when importing JSON to the builder.
	 *
	 * @return void
	 */
	public function unparse() {
		$this->unparse_default_values()
			->unparse_boolean_values()
			->unparse_numeric_values()
			->unparse_datalist()
			->unparse_choice_options()
			->unparse_choice_std()
			->unparse_clone()
			->unparse_array_attributes( 'options' )
			->unparse_array_attributes( 'js_options' )
			->unparse_array_attributes( 'query_args' )
			->unparse_array_attributes( 'attributes' )
			->unparse_text_limiter()
			->unparse_conditional_logic()
			->unparse_tooltip()
			->unparse_admin_columns()
			->unparse_custom_settings()
			->ensure_boolean( 'save_field' );

		$func = "unparse_field_{$this->type}";
		if ( method_exists( $this, $func ) ) {
			$this->$func();
		}
	}

	private function unparse_datalist() {
		if ( empty( $this->datalist ) ) {
			return $this;
		}

		$this->settings['datalist_choices'] = implode( "\n", $this->datalist['options'] );

		return $this;
	}

	/**
	 * Inverse of parse_choice_options.
	 *
	 * Convert options array to string.
	 *
	 * @return static
	 */
	private function unparse_choice_options() {
		if ( ! in_array( $this->type, $this->choice_types, true ) ) {
			return $this;
		}

		if ( empty( $this->options ) || ! is_array( $this->options ) ) {
			return $this;
		}

		$options = [];
		foreach ( $this->options as $key => $value ) {
			$options[] = "{$key}:{$value}";
		}
		$options = implode( "\r\n", $options );

		$this->options = $options;

		return $this;
	}

	private function unparse_choice_std() {
		if ( ! in_array( $this->type, $this->choice_types, true ) ) {
			return $this;
		}

		$this->std = is_array( $this->std ) ? implode( "\r\n", $this->std ) : $this->std;

		return $this;
	}

	private function unparse_clone() {
		if ( ! $this->clone ) {
			return $this;
		}

		$keys = [ 'sort_clone', 'clone_default', 'clone_as_multiple', 'min_clone', 'max_clone', 'add_button', 'clone_empty_start' ];

		foreach ( $keys as $key ) {
			if ( isset( $this->$key ) ) {
				continue;
			}

			$numerics = [ 'min_clone', 'max_clone' ];
			$strings  = [ 'add_button' ];
			if ( in_array( $key, $numerics, true ) ) {
				$this->$key = 0;
			} elseif ( in_array( $key, $strings, true ) ) {
				$this->$key = '';
			} else {
				$this->$key = false;
			}
		}

		return $this;
	}

	private function unparse_field_key_value() {
		$placeholder = $this->placeholder;
		if ( empty( $placeholder ) ) {
			return $this;
		}

		$this->placeholder_key   = $placeholder['key'] ?? '';
		$this->placeholder_value = $placeholder['value'] ?? '';

		return $this;
	}

	private function unparse_field_group() {
		$this->default_state = 'expanded';

		$keys = [ 'default_state', 'save_state', 'group_title' ];

		foreach ( $keys as $key ) {
			$this->$key = $this->$key ?? '';
		}

		return $this;
	}

	private function unparse_text_limiter() {
		if ( ! isset( $this->limit ) ) {
			return $this;
		}

		$this->text_limiter = [
			'limit'      => $this->limit,
			'limit_type' => $this->limit_type ?? 'word',
		];

		return $this;
	}

	private function unparse_tooltip() {
		if ( ! isset( $this->tooltip ) ) {
			return $this;
		}

		$defaults = [
			'enable'     => true,
			'icon'       => 'info',
			'position'   => 'top',
			'content'    => '',
			'allow_html' => true,
		];

		if ( is_string( $this->tooltip ) ) {
			$this->tooltip = [
				'content' => $this->tooltip,
			];
		}

		$this->tooltip = array_merge( $defaults, $this->tooltip );

		return $this;
	}

	private function unparse_admin_columns() {
		if ( ! isset( $this->admin_columns ) ) {
			return $this;
		}

		$defaults = [
			'enable'     => true,
			'position'   => 'after title',
			'title'      => '',
			'before'     => '',
			'after'      => '',
			'sort'       => false,
			'searchable' => false,
			'filterable' => false,
			'link'       => false,
		];

		if ( is_bool( $this->admin_columns ) ) {
			$this->admin_columns = [ 'enable' => $this->admin_columns ];
		} elseif ( is_string( $this->admin_columns ) ) {
			$this->admin_columns = [
				'enable'   => true,
				'position' => $this->admin_columns,
			];
		}

		$this->admin_columns = array_merge( $defaults, $this->admin_columns );
		return $this;
	}

	/**
	 * Inverse of parse_custom_settings.
	 *
	 * Detects unknown field-level attributes and converts them into the
	 * custom_settings format ({id, key, value}) that the Builder UI can render.
	 */
	private function unparse_custom_settings(): self {
		$known  = array_flip( self::$known_keys );
		$custom = $this->custom_settings ?? [];

		foreach ( array_diff_key( $this->settings, $known ) as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$uid            = uniqid();
			$custom[ $uid ] = [
				'id'    => $uid,
				'key'   => $key,
				'value' => (string) $value,
			];
			unset( $this->settings[ $key ] );
		}

		if ( ! empty( $custom ) ) {
			$this->custom_settings = $custom;
		}

		return $this;
	}

	public function unparse_default_values() {
		$this->id  = $this->id ?? uniqid();
		$this->_id = $this->_id ?? $this->id;

		$key_defaults = [
			'id'                => $this->id,
			'_id'               => $this->_id,
			'save_field'        => true,
			'label_description' => '',
			'desc'              => '',
			'size'              => '',
			'hide_from_rest'    => false,
			'hide_from_front'   => false,
			'before'            => '',
			'after'             => '',
			'class'             => '',
			'sanitize_callback' => '',
			'required'          => false,
			'disabled'          => false,
			'readonly'          => false,
			'prepend'           => '',
			'append'            => '',
		];

		foreach ( $key_defaults as $key => $default ) {
			$this->add_default( $key, $default );
		}

		return $this;
	}

	protected function ensure_boolean( $key ) {
		if ( isset( $this->$key ) ) {
			$this->$key = (bool) $this->$key;
		}
		return $this;
	}
}
