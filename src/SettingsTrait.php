<?php
namespace MBBParser;

/**
 * Use overloading magic methods for short syntax.
 * Property accesses are recorded so unparse_custom_settings() can detect
 * which keys were touched (= natively handled) vs untouched (= potentially custom).
 */
trait SettingsTrait {
	protected $settings;

	/**
	 * Keys accessed via __get / __set / __isset during this object's lifetime.
	 * Stored as a hash-map (key => true) for O(1) lookup.
	 *
	 * @var array<string, true>
	 */
	private $accessed_keys = [];

	public function get_settings(): array {
		return $this->settings;
	}

	/** Return all keys that were accessed (touched) so far. */
	public function get_accessed_keys(): array {
		return $this->accessed_keys;
	}

	public function __get( string $key ) {
		$this->accessed_keys[ $key ] = true;
		return $this->settings[ $key ] ?? null;
	}

	public function __set( string $key, $value ): void {
		$this->accessed_keys[ $key ] = true;
		$this->settings[ $key ] = $value;
	}

	public function __isset( string $key ): bool {
		$this->accessed_keys[ $key ] = true;
		return isset( $this->settings[ $key ] );
	}

	public function __unset( string $key ): void {
		unset( $this->settings[ $key ] );
	}
}
