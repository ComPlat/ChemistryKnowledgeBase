<?php
namespace DIQA\FacetedSearch2\Update;

use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;
use Title;
use WikiPage;

class FacetedSearchUtil {


    /**
     * Retrieves the display title from the properties table for the given page.
     * It will probably only properly work if the DisplayTitles extension is installed and used.
     * The default value is the pagename.
     * 
     * This code is inspired by getDisplayTitle() from DisplayTitle\includes\DisplayTitleHooks.php
     * 
     * @param Title $title
     * @param WikiPage $wikipage (optional) if present redirects will be followed
     * @return string smwh_displaytitle
     */
    public static function findDisplayTitle(Title $title, WikiPage $wikipage = null) {
        $title = $title->createFragmentTarget( '' );
        $originalPageName = $title->getText();
        
        $redirect = false;
        if($wikipage) {
            $redirectTarget = MediaWikiServices::getInstance()->getRedirectLookup()->getRedirectTarget( $wikipage );
            if ( !is_null( $redirectTarget ) ) {
                $redirect = true;
                $title = Title::makeTitle( $redirectTarget->getNamespace(), $redirectTarget->getDBkey() );
            }
        }
        
        $id = $title->getArticleID();
        $values = MediaWikiServices::getInstance()->getPageProps()->getProperties( $title, 'displaytitle' );

        if ( array_key_exists( $id, $values ) ) {
            $value = $values[$id];
            if ( trim( str_replace( '&#160;', '', strip_tags( $value ) ) ) !== '' ) {
                return $value;
            }
        } elseif ( $redirect ) {
            return  $title->getPrefixedText();
        }
        return $originalPageName;
    }

    public static function getISODateFromDataItem(\SMWDataItem $dataItem): string
    {
        $year = $dataItem->getYear();
        $month = $dataItem->getMonth();
        $day = $dataItem->getDay();

        $hour = $dataItem->getHour();
        $min = $dataItem->getMinute();
        $sec = $dataItem->getSecond();

        $month = strlen($month) === 1 ? "0$month" : $month;
        $day = strlen($day) === 1 ? "0$day" : $day;
        $hour = strlen($hour) === 1 ? "0$hour" : $hour;
        $min = strlen($min) === 1 ? "0$min" : $min;
        $sec = strlen($sec) === 1 ? "0$sec" : $sec;

        // Required format: 1995-12-31T23:59:59Z
        return "{$year}-{$month}-{$day}T{$hour}:{$min}:{$sec}Z";
    }
}