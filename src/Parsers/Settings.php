<?php
namespace MBBParser\Parsers;

use MetaBox\Support\Arr;

class Settings extends Base {
	// Allow these settings to be empty.
	protected $empty_keys = ['post_types', 'taxonomies', 'settings_pages'];

	public function parse() {
		$this->remove_default( 'context', 'normal' )
			->parse_boolean_values()
			->parse_numeric_values()
			->parse_location()
			->parse_location_rules( 'show_hide' )
			->parse_location_rules( 'include_exclude' )
			->parse_conditional_logic()
			->parse_custom_table()
			->parse_block()
			->parse_custom_settings()
			->remove_empty_values();

		unset( $this->object_type );
	}

	private function parse_location() {
		$object_type = $this->object_type ? $this->object_type : 'post';

		if ( in_array( $object_type, ['user', 'comment', 'block'], true ) ) {
			unset( $this->$object_type );
			$this->type = $object_type;
		}

		if ( 'post' === $object_type ) {
			$this->remove_default( 'post_types', ['post'] );
			$this->remove_default( 'priority', 'high' );
			$this->remove_default( 'style', 'default' );
			$this->remove_default( 'position', 'normal' );
		}

		return $this;
	}

	private function parse_location_rules( $key ) {
		if ( ! isset( $this->$key ) ) {
			return $this;
		}

		$data = $this->$key;
		unset( $this->$key );

		$rules = [];
		foreach ( $data['rules'] as $rule ) {
			$value = $rule['value'];
			if ( 'input_value' === $rule['name'] ) {
				$value = wp_list_pluck( $value, 'value', 'key' );
			}
			$rules[ $rule['name'] ] = $value;
		}
		$type = $data['type'];

		$this->$type = array_merge( [
			'relation' => $data['relation'],
		], $rules );

		return $this;
	}

	private function parse_custom_table() {
		$enable = Arr::get( $this->settings, 'custom_table.enable', false );
		$name = Arr::get( $this->settings, 'custom_table.name', '' );
		if ( $enable && $name ) {
			$this->storage_type = 'custom_table';

			global $wpdb;
			$prefix = Arr::get( $this->settings, 'custom_table.prefix', false );
			$this->table = ( $prefix ? $wpdb->prefix : '' ) . $name;
		}

		unset( $this->custom_table );
		return $this;
	}

	private function parse_block() {
		// Remove block settings.
		if ( 'block' !== $this->object_type ) {
			$params = [
				'description', 'category', 'keywords', 'supports', 'block_context',
				'icon', 'icon_type', 'icon_svg', 'icon_background', 'icon_foreground',
				'render_with', 'render_template', 'render_callback', 'render_code',
				'enqueue_style', 'enqueue_script', 'enqueue_assets'
			];
			foreach ( $params as $param ) {
				unset( $this->{$param} );
			}
			return $this;
		}

		$this->keywords = Arr::from_csv( $this->keywords );

		// Icon.
		if ( 'dashicons' === $this->icon_type ) {
			if ( $this->icon_background || $this->icon_foreground ) {
				$this->icon = [
					'background' => $this->icon_background,
					'foreground' => $this->icon_foreground,
					'src'        => $this->icon,
				];
			}
		}
		if ( 'svg' === $this->icon_type ) {
			$this->icon = $this->icon_svg;
		}
		unset( $this->icon_svg );
		unset( $this->icon_background );
		unset( $this->icon_foreground );
		unset( $this->icon_type );

		// Render options.
		if ( 'callback' === $this->render_with ) {
			unset( $this->render_template );
		}
		if ( 'template' === $this->render_with ) {
			unset( $this->render_callback );
			$this->render_template = $this->replace_variables( $this->render_template );
		}
		if ( 'code' === $this->render_width ) {
			unset( $this->render_template );
			unset( $this->render_callback );
		}
		$this->enqueue_style = $this->replace_variables( $this->enqueue_style );
		$this->enqueue_script = $this->replace_variables( $this->enqueue_script );

		unset( $this->render_with );

		// Context.
		$this->context = $this->block_context;
		unset( $this->block_context );

		return $this;
	}

	private function replace_variables( $string ) {
		return strtr( $string, [
			'{{ site.path }}'  => wp_normalize_path( ABSPATH ),
			'{{ site.url }}'   => untrailingslashit( home_url( '/' ) ),
			'{{ theme.path }}' => wp_normalize_path( get_stylesheet_directory() ),
			'{{ theme.url }}'  => get_stylesheet_directory_uri(),
		] );
	}
}
