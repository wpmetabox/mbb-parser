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
	 * Used by unparse_custom_settings() to detect natively-handled keys.
	 *
	 * @var string[]
	 */
	private array $accessed_keys = [];

	public function get_settings(): array {
		return $this->settings;
	}

	/** Return all keys that were accessed (touched) so far. */
	public function get_accessed_keys(): array {
		return $this->accessed_keys;
	}

	public function __get( string $key ) {
		$this->accessed_keys[] = $key;
		return $this->settings[ $key ] ?? null;
	}

	public function __set( string $key, $value ): void {
		$this->accessed_keys[] = $key;
		$this->settings[ $key ] = $value;
	}

	public function __isset( string $key ): bool {
		$this->accessed_keys[] = $key;
		return isset( $this->settings[ $key ] );
	}

	public function __unset( string $key ): void {
		unset( $this->settings[ $key ] );
	}
}
