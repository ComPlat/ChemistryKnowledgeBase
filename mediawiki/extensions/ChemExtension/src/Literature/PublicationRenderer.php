<?php
namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use OutputPage;
use ParserOptions;
use RequestContext;
use Text;

class PublicationRenderer {

    public static function getAuthorFromOrcid($orcid) {
        $result = QueryUtils::executeBasicQuery("[[Orcid::$orcid]]",
            [QueryUtils::newPropertyPrintRequest("Author")], ['mainlabel' => '-', 'limit' => 1]);
        $author = null;
        if ($row = $result->getNext()) {

            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem !== false) {
                $author = $dataItem->getString();
            }
        }
        return $author;
    }

    public static function renderPublicationList($orcid) {

        $query = <<<QUERY
{{#ask:
[[Has subobject::<q>[[Orcid::$orcid]]</q>]]
|?DOI
|?Journal
|?Publication date
|?Publisher
|mainlabel=Publication
|format=table
|default=--no-publications-yet--
}}
QUERY;
        global $wgTitle;
        $title = $wgTitle ?? RequestContext::getMain()->getTitle();
        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($query, $title, new ParserOptions(RequestContext::getMain()->getUser()));
        return $parserOutput->getText(['enableSectionEditLinks' => false]);

    }

    public static function renderPublicationListByAuthor($author) {


        $query = <<<QUERY
{{#ask:
[[Has subobject::<q>[[Author::$author]][[Orcid::-]]</q>]]
|?DOI
|?Journal
|?Publication date
|?Publisher
|mainlabel=Publication
|format=table
|default=--no-publications-yet--
}}
QUERY;
        global $wgTitle;
        $title = $wgTitle ?? RequestContext::getMain()->getTitle();
        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($query, $title, new ParserOptions(RequestContext::getMain()->getUser()));
        return $parserOutput->getText(['enableSectionEditLinks' => false]);

    }
}
