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
			->unparse_admin_columns();

		// Field-type handler must run BEFORE unparse_custom_settings() so its
		// property accesses are tracked and not mis-classified as custom attributes.
		$func = "unparse_field_{$this->type}";
		if ( method_exists( $this, $func ) ) {
			$this->$func();
		}

		$this->unparse_custom_settings();
		$this->ensure_boolean( 'save_field' );
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
		// Accept fields that only export limit_type (limit=0 is stripped on export as default).
		if ( ! isset( $this->limit ) && ! isset( $this->limit_type ) ) {
			return $this;
		}

		$this->text_limiter = [
			'limit'      => $this->limit ?? 0,
			'limit_type' => $this->limit_type ?? 'word',
		];

		// Remove flat keys so they are not double-stored alongside text_limiter.
		unset( $this->settings['limit'], $this->settings['limit_type'] );

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
	 * Pure pass-through keys that no unparse_* method ever reads via $this->key.
	 * They are kept in settings as-is, so they must never be classified as
	 * user-defined custom attributes.
	 *
	 * Every other native key is auto-tracked by SettingsTrait the moment any
	 * method does $this->key — no need to list them here.
	 *
	 * Extend via filter:
	 *   add_filter( 'mbb_parser_known_field_keys', fn( $k ) => array_merge( $k, [ 'my_key' ] ) );
	 */
	private const STRUCTURAL_KEYS = [
		'type', 'id', '_id', 'name', 'std', 'placeholder',
		'columns', 'field_name', '_state', 'input_attributes',
		'multiple', 'fields', 'tab', 'validation',
		'custom_settings', '_callback', 'options',
	];

	/**
	 * Detect unknown top-level keys and move them into the custom_settings
	 * format `{ uid: { id, key, value } }` that the Builder UI renders in
	 * the "Custom Settings" tab.
	 *
	 * "Known" = auto-tracked by SettingsTrait + STRUCTURAL_KEYS pass-through list.
	 */
	private function unparse_custom_settings(): self {
		$always_native = apply_filters( 'mbb_parser_known_field_keys', self::STRUCTURAL_KEYS );
		$known         = $this->get_accessed_keys() + array_flip( $always_native );
		$custom        = $this->custom_settings ?? [];

		foreach ( array_diff_key( $this->settings, $known ) as $key => $value ) {
			if ( is_resource( $value ) ) {
				continue;
			}

			$uid            = uniqid();
			$custom[ $uid ] = [
				'id'    => $uid,
				'key'   => $key,
				'value' => ( is_array( $value ) || is_object( $value ) )
					? wp_json_encode( $value )
					: (string) $value,
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
