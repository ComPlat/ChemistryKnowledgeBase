<?php

namespace DIQA\Util\ParserFunctions;

use Parser;

/**
 * Parserfunction to remove all HTML tags.
 *
 * @author Kai
 *
 */
class StripTags {

	/**
	 * Render the output of {{stripTags}}.
	 */
	static function stripTags(Parser &$parser, $input = '') {
	    //$parser->disableCache ();

	    $output = strip_tags($input, '');
	    $output = trim(str_replace(array("\r\n", "\n", "\r", "\n\r", "\t"), ' ', $output));
	    	    
		return array($output, 'noparse' => true);
	}
	
	/**
	 * Registers parser hook for MW.
	 *
	 * @param Parser $parser
	 */
	public static function registerParserHooks(Parser &$parser) {
		$parser->setFunctionHook('stripTags', 'DIQA\Util\ParserFunctions\StripTags::stripTags');
	}
}
