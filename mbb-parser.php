<?php
/**
 * Plugin Name:     MBB Parser
 * Plugin URI:      https://metabox.io
 * Description:     Parser meta box settings from JSON
 * Author:          MetaBox.io
 * Author URI:      https://metabox.io
 */

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}
new MBBParser\RestApi;