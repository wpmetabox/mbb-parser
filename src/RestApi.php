<?php
namespace MBBParser;

use WP_REST_Server;
use WP_REST_Request;

class RestApi {
	public function __construct() {
		add_action( 'rest_api_init', [$this, 'register_routes'] );
	}

	public function register_routes() {
		register_rest_route( 'mbbparser', 'settings', [
			'method'  => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_meta_box_settings' ],
		] );
		register_rest_route( 'mbbparser', 'code', [
			'method'  => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_meta_box_code' ],
		] );
	}

	public function get_meta_box_code( WP_REST_Request $request ) {
		$settings = $this->get_meta_box_settings( $request );
		$encoder = new Encoders\MetaBox( $settings );
		$encoder->encode();

		return $encoder->get_encoded_string();
	}

	public function get_meta_box_settings( WP_REST_Request $request ) {
		$parser = new Parsers\MetaBox( $request->get_params() );
		$parser->parse();

		return $parser->get_settings();
	}
}