<?php

namespace DIQA\ChemExtension\TemplateParser;

/**
 * Parses the template calls of a wikipage and allows changing their parameters.
 *
 * Restrictions:
 * 	1. No template/parserfunction calls within parameters.
 *  2. Only 1 template of each type
 *
 * @author Kai, Michael
 *
 */
class TemplateEditor {

	const TEMPLATE_PATTERN = '/\{\{(\w+)(([^}]*|(\}[^}]))*)\}\}/i';

	private $wikitext;
	// array of template names in the order they are called in the page
	private $templateNames;
	// array of arrays (of template parameter to value mappings) for the templates in templateNames (in the same order)
	private $templateParams;

	public function __construct($wikitext = '') {
		$this->wikitext = $wikitext;
		$this->parseTemplates ( $wikitext );
	}

	public function getWikiText() {
		return $this->wikitext;
	}
	
	

	/**
	 * Returns all found templates
	 * @return the $templateNames
	 */
	public function getTemplateNames() {
		return $this->templateNames;
	}

	/**
	 * Returns the template parameters.
	 * 
	 * @param string templateName
	 * @return the $templateParams
	 */
	public function getTemplateParams($templateName) {
		return array_key_exists($templateName, $this->templateParams) ? $this->templateParams[$templateName] : [];
	}

	/**
	 * @param  string  $templateName name of the template for which to update the parameters
	 * @param  array   $params attribute/value mapping for new or updated template parameters
	 * @return string  the resulting wikitext
	 */
	public function replaceTemplateParameters($templateName, array $params) {
		
		$this->wikitext = preg_replace_callback ( static::TEMPLATE_PATTERN, function (array $matches) use($templateName, $params) {
			$currentTemplateName = $matches [1];
			$newParams = $this->templateParams[$templateName];
			
			if ($templateName == $currentTemplateName) {
				$newParams = array_merge ( $newParams, $params );
			}
			return $this->serializeTemplate ( $currentTemplateName, $newParams );
		}, $this->wikitext );
		return $this->wikitext;
	}

	public function serializeTemplate($templateName, $params) {
		$text = '{{' . $templateName;
		foreach ( $params as $key => $value ) {
			if($value === null) {
				$text .= "\n|$key";
			} else {
				$text .= "\n|$key=$value";
			}
		}
		$text .= "\n}}";
		return $text;
	}

	/**
	 * instantiates $this->templateNames ans $this->templateParams
	 * @param string $wikitext
	 */
	private function parseTemplates($wikitext) {
		$this->templateNames= array();
		$this->templateParams= array();

		$matches = array ();
		$success = preg_match_all ( static::TEMPLATE_PATTERN, $wikitext, $matches, PREG_SET_ORDER );

		if ($success === false || $success == 0) {
			return;
		}

 		foreach ( $matches as $m ) {
			$templateName = $m[1];
			$templateParameters = $m[2];

			$this->templateNames [] = $templateName;
			$this->templateParams[$templateName] = $this->parseTemplateParameters ( $templateParameters );
 		}
	}

	/**
	 *
	 * @param  string  $templateParameters of the form foo=bar|foo2=bar2|...
	 * @return array   parameter->value
	 */
	private function parseTemplateParameters($templateParameters) {
		$params = explode ( "|", $templateParameters );
		$results = array ();
		foreach ( $params as $p ) {
			$p = trim ( $p );
			if ($p == '') {
				continue;
			}
			$parts = explode ( "=", $p ,2);
			if (count ( $parts ) < 2) {
				// parameters without values will be marked as having a value of NULL
				$key = $parts[0];
				$value = null;
			} else {
				list ( $key, $value ) = $parts;
			}
			$results [$key] = $value;
		}

		return $results;
	}
}