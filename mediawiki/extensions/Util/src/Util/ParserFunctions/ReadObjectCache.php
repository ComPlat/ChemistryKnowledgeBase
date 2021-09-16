<?php

namespace DIQA\Util\ParserFunctions;

use Parser;

/**
 * Parserfunction to read keys from ObjectCache
 * 
 * @author Kai
 *
 */
class ReadObjectCache {

	/**
	 * Read key from ObjectCache
	 */
	static function readObjectCache(Parser &$parser, $key = '') {
	    //$parser->disableCache ();

	    $cache = \ObjectCache::getInstance(CACHE_DB);
	    
	    global $wgDIQAAllowedObjectCacheKeys;
	    if (!isset($wgDIQAAllowedObjectCacheKeys) 
	    	|| !in_array($key, $wgDIQAAllowedObjectCacheKeys)) {
	    	return 'not-allowed-to-access';
	    }
	    
		$value = $cache->get($key);
	    if (is_array($value)) {
	    	return "$key is an array";
	    }	    
	    if (is_object($value)) {
	    	return "$key is an object";
	    }
	    
		return array((string)$value, 'noparse' => true);
	}
	
	/**
	 * Registers parser hook for MW.
	 * 
	 * @param Parser $parser
	 */
	public static function registerParserHooks(Parser &$parser) {
		
		$parser->setFunctionHook('readObjectCache', 'DIQA\Util\ParserFunctions\ReadObjectCache::readObjectCache');
		
	}

	
}
