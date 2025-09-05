<?php
namespace BWK\Accounting;

class Autoloader {
    public static function register() : void {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    private static function autoload( string $class ) : void {
        $prefix = __NAMESPACE__ . '\\';
        $len    = strlen( $prefix );
        if ( 0 !== strncmp( $prefix, $class, $len ) ) {
            return;
        }
        $relative = substr( $class, $len );
        $relative_path = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
        $file = BWK_ACC_PATH . $relative_path . '.php';
        if ( file_exists( $file ) ) {
            require $file;
        }
    }
}
