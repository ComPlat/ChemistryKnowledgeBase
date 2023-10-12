<?php

class UrlGetParametersHooks {

	/**
	 * Hook to load our parser function.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'urlget', [ __CLASS__, 'urlGetParametersRender' ] );
	}

	/**
	 * @param Parser $parser
	 * @return string
	 */
	public static function urlGetParametersRender( $parser ) {
		global $wgUrlGetParametersSeparator;

		// {{#urlget:paramname|defaultvalue}}

		// Get the parameters that were passed to this function
		$params = func_get_args();
		array_shift( $params );

		// Cache needs to be disabled for URL parameters to be retrieved correctly
		$parser->getOutput()->updateCacheExpiry( 0 );

		// Check whether this param is an array, i.e. of the form "a[b]"
		$pos_left_bracket = strpos( $params[0], '[' );
		$pos_right_bracket = strpos( $params[0], ']' );

		if ( !$pos_left_bracket || !$pos_right_bracket ) {
			if ( isset( $_GET[$params[0]] ) ) {
				// Allow array
				if ( is_array( $_GET[$params[0]] ) ) {
					$listval = [];
					foreach ( $_GET[$params[0]] as $selectedOption ) {
						array_push( $listval, rawurlencode( $selectedOption ) );
					}
					return implode( $wgUrlGetParametersSeparator, $listval );
				} else {
					return rawurlencode( $_GET[$params[0]] );
				}
			}
		} else {
			// It's an array
			$key = substr( $params[0], 0, $pos_left_bracket );
			$value = substr( $params[0], $pos_left_bracket + 1, ( $pos_right_bracket - $pos_left_bracket - 1 ) );

			if ( isset( $_GET[$key] ) && isset( $_GET[$key][$value] ) ) {
				return rawurlencode( $_GET[$key][$value] );
			}
		}
		if ( count( $params ) > 1 ) {
			return rawurlencode( $params[1] );
		}
		return '';
	}
}
