<?php

namespace DIQA\FacetedSearch2\Utils;

use DIQA\FacetedSearch2\Update\FacetedSearchUtil;
use MediaWiki\MediaWikiServices;
use Title;
use RequestContext;

class WikiTools {

    public static function createURLForPage(string $title, int $namespace = 0) {
        if (!defined('MEDIAWIKI')) {
            return 'n/a';
        }
        $title = Title::newFromText($title, $namespace);
        return $title->getFullURL();
    }

    public static function createURLForProperty(string $title) {
        if (!defined('MEDIAWIKI')) {
            return 'n/a';
        }
        $title = Title::newFromText($title, SMW_NS_PROPERTY);
        return $title->getFullURL();
    }

    public static function createURLForCategory(string $title) {
        if (!defined('MEDIAWIKI')) {
            return 'n/a';
        }
        $title = Title::newFromText($title, NS_CATEGORY);
        return $title->getFullURL();
    }

    public static function getNamespaceName(int $namespace) {
        if (!defined('MEDIAWIKI')) {
            return 'n/a';
        }
        $contLang = MediaWikiServices::getInstance()->getContentLanguage();
        return $contLang->getNsText($namespace);
    }

    public static function getDisplayTitleForProperty(string $title) {
        if (!defined('MEDIAWIKI')) {
            return ucfirst($title);
        }
        return FacetedSearchUtil::findDisplayTitle(Title::newFromText($title, SMW_NS_PROPERTY));
    }

    public static function getDisplayTitleForCategory(string $title) {
        if (!defined('MEDIAWIKI')) {
            return ucfirst($title);
        }
        return FacetedSearchUtil::findDisplayTitle(Title::newFromText($title, NS_CATEGORY));
    }

    public static function getUserGroups() {
        if (!defined('MEDIAWIKI')) {
            return ['sysop', 'user'];
        }
        $user = RequestContext::getMain()->getUser();
        $userGroups = MediaWikiServices::getInstance()
            ->getUserGroupManager()
            ->getUserGroups( $user );
        // every users is treated as being a member of "user"
        if (! in_array('user', $userGroups)) {
            $userGroups[] = 'user';
        }
        return $userGroups;
    }

    /**
     * Strip all HTML tags except <b>, <sub>, <sup>, and remove attributes from them.
     */
    public static function stripHtml(string $html): string {
        // Keep only the allowed tags; everything else is stripped
        $allowed = '<b><sub><sup>';
        $stripped = strip_tags($html, $allowed);

        // Remove attributes from allowed tags to prevent XSS via attributes
        $normalized = preg_replace('/<(b|sub|sup)\b[^>]*>/i', '<$1>', $stripped);

        // Optional: decode dangerous entities that could reintroduce tags, then re-strip
        // (defense-in-depth if input might contain encoded tags like &lt;script&gt;)
        $decoded = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = strip_tags($decoded, $allowed);
        $decoded = preg_replace('/<(b|sub|sup)\b[^>]*>/i', '<$1>', $decoded);

        return $decoded;
    }
}
