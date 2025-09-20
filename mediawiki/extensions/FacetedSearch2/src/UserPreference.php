<?php

namespace DIQA\FacetedSearch2;

use MediaWiki\MediaWikiServices;

class UserPreference
{
    public static $defaultSortOrder = "sort-alphabetically";

    static public function setupPreferences($user, &$preferences)
    {
        $options = [];

        $options[wfMessage('sort-by-count')->text()] = "sort-by-count";
        $options[wfMessage('sort-alphabetically')->text()] = "sort-alphabetically";

        $preferences ['fs2-sort-order-preferences'] = array(
            'type' => 'radio',
            'label' => '&#160;',
            'label-message' => 'prefs-Standard-Sortierung-Facetten', // a system message
            'section' => 'facetedsearch2',
            'options' => $options,
            'help-message' => 'Suchoptionen'  // a system message (optional)
        );

        $option = MediaWikiServices::getInstance()->getUserOptionsLookup()->getOption(
            $user, 'fs2-sort-order-preferences', null);
        if (is_null($option)) {
            $preferences ['fs2-sort-order-preferences'] ['default'] = self::$defaultSortOrder;
        }

        return true;
    }
}