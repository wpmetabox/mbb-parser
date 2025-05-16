<?php
namespace MBBParser\Parsers;

class FieldJson extends Base {
	public function parse(): void {
		$this->parse_fields( $this->settings );
	}

	private function parse_fields( &$fields ): void {
		array_walk( $fields, [ $this, 'parse_field' ] );
	}

	private function parse_field( &$field ): void {
		if ( ! is_string( $field ) ) {
			return;
		}

		$json = json_decode( wp_unslash( $field ), true );
		if ( ! is_array( $json ) || json_last_error() !== JSON_ERROR_NONE ) {
			return;
		}

		$field = $json;

		if ( isset( $field['fields'] ) ) {
			$this->parse_fields( $field['fields'] );
		}
	}
}
