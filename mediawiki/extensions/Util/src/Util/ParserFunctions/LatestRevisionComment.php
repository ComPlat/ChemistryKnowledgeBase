<?php

namespace DIQA\Util\ParserFunctions;

use Parser;
use WikiPage;
use Title;
use Revision;

/**
 * Parserfunction to return the comment of the latest revision of the current page.
 * 
 * @author Daniel
 *
 */
class LatestRevisionComment {
	/**
	 * return the comment of the latest revision of the current page
	 * parameter: page title
	 */
	static function latestRevisionComment(Parser &$parser, $input = '') {
	    //$parser->disableCache ();
	    $titleObj = Title::newFromText( $input ); // titel ist parameter
	    $article = WikiPage::factory( $titleObj );
	    $comment = $article->getComment(Revision::RAW,  null);
	    $output = strip_tags($comment, '');
	    $output = trim(str_replace(array("\r\n", "\n", "\r", "\n\r", "\t"), ' ', $output));
	    $output = LatestRevisionComment::wikitextEncode($output);
	    if (empty($output)) {
	        $output = "''n/a''";
	    }
		return array($output, 'noparse' => true);
	}
	
	static function wikitextEncode($string) {
	    $entities = array('{', '[', "|", "]", "}");
	    $replacements = array('&#123;', '&#91;', '&#124;', '&#93;', '&#125;');
	    return str_replace($entities, $replacements, $string);
	}
	
	/**
	 * Registers parser hook for MW.
	 * 
	 * @param Parser $parser
	 */
	public static function registerParserHooks(Parser &$parser) {
		$parser->setFunctionHook('latestRevisionComment', 'DIQA\Util\ParserFunctions\LatestRevisionComment::latestRevisionComment');
	}

}

