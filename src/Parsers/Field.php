<?php
namespace MBBParser\Parsers;

class Field extends Base {
	// Allow these settings to be empty.
	protected $empty_keys = ['save_field'];

	// Remove if "true", set to "false" if missing.
	protected $default_true = [
		'button_group'   => ['inline'],
		'radio'          => ['inline'],
		'file_advanced'  => ['max_status'],
		'file_upload'    => ['max_status'],
		'image_advanced' => ['max_status'],
		'image_upload'   => ['max_status'],
		'video'          => ['max_status'],
	];
	private $choice_types = ['select', 'radio', 'checkbox_list', 'select_advanced', 'button_group', 'image_select', 'autocomplete'];

	public function parse() {
		// Remove collapse/expand state.
		unset( $this->_state );

		// Remove unique ID used for tracking in JS.
		unset( $this->_id );

		// Remove default fields.
		$this->remove_default( 'save_field', true )
			->remove_default( 'add_to', 'end' ) // image_advanced.
			->remove_default( 'image_size', 'thumbnail' ) // image_advanced.
			->parse_boolean_values()
			->parse_numeric_values()
			->parse_datalist()
			->parse_object_field()
			->parse_choice_options()
			->parse_choice_std()
			->parse_clone()
			->parse_array_attributes( 'options' )
			->parse_array_attributes( 'js_options' )
			->parse_array_attributes( 'query_args' )
			->parse_array_attributes( 'attributes' )
			->parse_custom_settings()
			->parse_conditional_logic()
			->parse_upload_dir()
			->remove_empty_values()
			->parse_default_true();

		// Field-specific parser.
		$func = "parse_field_{$this->type}";
		if ( method_exists( $this, $func ) ) {
			$this->$func();
		}

		$this->settings = apply_filters( 'mbb_field_settings', $this->settings );
		$this->settings = apply_filters( "mbb_field_settings_{$this->type}", $this->settings );
	}

	private function parse_datalist() {
		if ( empty( $this->settings['datalist_choices'] ) ) {
			return $this;
		}
		$this->datalist = [
			'id'      => uniqid(),
			'options' => explode( "\n", $this->settings['datalist_choices'] ),
		];
		unset( $this->settings['datalist_choices'] );
		return $this;
	}

	private function parse_object_field() {
		if ( ! in_array( $this->type, array( 'taxonomy', 'taxonomy_advanced', 'post', 'user' ), true ) ) {
			return $this;
		}
		unset( $this->terms );

		/**
		 * Available field types:
		 * - select
		 * - select_advanced
		 * - select_tree
		 * - checkbox_list
		 * - checkbox_tree
		 * - radio_list
		 */

		if ( in_array( $this->field_type, array( 'select', 'select_advanced', 'select_tree', 'checkbox_tree' ), true ) ) {
			unset( $this->inline );
		}
		if ( in_array( $this->field_type, array( 'select_tree', 'checkbox_list', 'checkbox_tree', 'radio_list' ), true ) ) {
			unset( $this->multiple );
		}
		if ( in_array( $this->field_type, array( 'select_tree', 'checkbox_tree', 'radio_list' ), true ) ) {
			unset( $this->select_all_none );
		}
		if ( empty( $this->multiple ) && in_array( $this->field_type, array( 'select', 'select_advanced' ), true ) ) {
			unset( $this->select_all_none );
		}

		return $this;
	}

	private function parse_choice_options() {
		if ( ! in_array( $this->type, $this->choice_types ) ) {
			return $this;
		}
		if ( empty( $this->options ) || is_array( $this->options ) ) {
			return $this;
		}

		// Use callback: function_name format.
		if ( is_string( $this->options ) && 0 === strpos( $this->options, 'callback:' ) ) {
			$callback = trim( str_replace( 'callback:', '', $this->options ) );
			if ( is_callable( $callback ) ) {
				$this->options = call_user_func( $callback );
				$this->_callback = $callback; // For using in the encoders.
			}
			return $this;
		}

		$options = array();

		$this->options = trim( wp_unslash( $this->options ) );
		$this->options = explode( "\n", $this->options );

		foreach ( $this->options as $choice ) {
			if ( false !== strpos( $choice, ':' ) ) {
				list( $value, $label )     = explode( ':', $choice, 2 );
				$options[ trim( $value ) ] = trim( $label );
			} else {
				$options[ trim( $choice ) ] = trim( $choice );
			}
		}

		$this->options = $options;

		return $this;
	}

	private function parse_choice_std() {
		$is_multiple = $this->multiple
			|| in_array( $this->type, ['checkbox_list', 'autocomplete'] )
			|| in_array( $this->field_type, ['select_tree', 'checkbox_tree', 'checkbox_list', 'checkbox_tree'] );

		if ( $is_multiple ) {
			$this->std = is_string( $this->std ) && ! empty( $this->std ) ? preg_split('/\r\n|\r|\n/', $this->std ) : $this->std;
		}

		if ( empty( $this->std ) ) {
			unset( $this->std );
		}

		return $this;
	}

	private function parse_clone() {
		if ( $this->clone ) {
			return $this;
		}
		$keys = ['sort_clone', 'clone_default', 'clone_as_multiple', 'max_clone', 'add_button'];
		foreach ( $keys as $key ) {
			unset( $this->$key );
		}
		return $this;
	}

	private function parse_upload_dir() {
		if ( $this->upload_dir ) {
			$this->upload_dir = trailingslashit( ABSPATH ) . untrailingslashit( $this->upload_dir );
		}
		return $this;
	}

	protected function parse_default_true() {
		if ( ! isset( $this->default_true[ $this->type ] ) ) {
			return $this;
		}
		$default_true = $this->default_true[ $this->type ];
		foreach ( $default_true as $key ) {
			if ( $this->$key === true ) {
				unset( $this->$key );
			} else {
				$this->$key = false;
			}
		}
	}

	private function parse_field_key_value() {
		$placeholder = [];
		if ( $this->placeholder_key ) {
			$placeholder['key'] = $this->placeholder_key;
		}
		if ( $this->placeholder_value ) {
			$placeholder['value'] = $this->placeholder_value;
		}
		$placeholder = array_filter( $placeholder );
		if ( $placeholder ) {
			$this->placeholder = $placeholder;
		}
		unset( $this->placeholder_key, $this->placeholder_value );
		return $this;
	}

	private function parse_field_group() {
		$this->remove_default( 'default_state', 'expanded' );
		if ( $this->collapsible ) {
			return $this;
		}
		$keys = ['default_state', 'save_state', 'group_title'];
		foreach ( $keys as $key ) {
			unset( $this->$key );
		}
		return $this;
	}
}
