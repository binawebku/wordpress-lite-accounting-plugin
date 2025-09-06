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
        $relative = ltrim( $relative, '\\' );

        // Map Admin namespace to admin directory; everything else lives in includes.
        if ( 0 === strpos( $relative, 'Admin\\' ) ) {
            $relative_path = substr( $relative, strlen( 'Admin\\' ) );
            $path          = 'admin/' . str_replace( '\\', '/', $relative_path ) . '.php';
        } else {
            $path = 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
        }

        $file = BWK_ACC_PATH . $path;
        if ( file_exists( $file ) ) {
            require $file;
        }
    }
}
