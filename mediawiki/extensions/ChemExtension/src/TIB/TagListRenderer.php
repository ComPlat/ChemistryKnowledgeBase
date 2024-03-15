<?php
namespace DIQA\ChemExtension\TIB;

use MediaWiki\MediaWikiServices;
use OutputPage;
use ParserOptions;
use RequestContext;
use Text;

class TagListRenderer {

    public static function renderTagList(OutputPage $out) {

        $query = <<<QUERY
== Tags ==
{{#ask:
[[-Has subobject::{{FULLPAGENAME}}]] 
|?Tag
|?Ontology
|?OBOID
|mainlabel=-
|format=list
|default=--no-tags-yet--
}}
QUERY;
        global $wgTitle;
        $title = $wgTitle ?? RequestContext::getMain()->getTitle();
        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($query, $title, new ParserOptions(RequestContext::getMain()->getUser()));
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);
        if (strpos($html, '--no-tags-yet--') === false) {
            $out->addHTML($html);
        }
    }
}
