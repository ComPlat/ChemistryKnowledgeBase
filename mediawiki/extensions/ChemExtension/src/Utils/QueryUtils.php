<?php
namespace DIQA\ChemExtension\Utils;

use Article;
use SMW\Query\QueryContext;
use Title;
use SMWDataItem;
use SMWDIWikiPage;
use SMWDITime;
use SMWQueryProcessor;
use SMW\DataValueFactory;
use SMW\DataValues\PropertyChainValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\PrintRequest;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory;
use SMW\StoreFactory;

/**
 * Utility class for executing SMW Queries
 *
 * @author Michael Erdmann
 */
class QueryUtils {

    /**
     * @param string queryString
     * @param array printouts
     * @param array parameters
     * @return SMWDIWikiPage[]
     */
    public static function executeQuery($queryString, $printouts = array(), $parameters = array()) {
        $smwQueryResult = static::executeBasicQuery($queryString, $printouts, $parameters);

        return $smwQueryResult->getResults();
    }

    /**
     * @param string queryString
     * @param array printouts
     * @param array parameters
     * @return QueryResult
     */
    public static function executeBasicQuery($queryString, $printouts = array(), $parameters = array()) {
        SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

        $smwQueryObject = SMWQueryProcessor::createQuery(
                $queryString,
                SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
                SMWQueryProcessor::SPECIAL_PAGE,
                '',
                $printouts
        );

        $smwStore = ServicesFactory::getInstance()->getStore();

        return $smwStore->getQueryResult( $smwQueryObject );
    }

    public static function executeBasicQueryCount($queryString, $printouts = [], $parameters = []) {
        SMWQueryProcessor::addThisPrintout($printouts, $parameters);

        $smwQueryObject = SMWQueryProcessor::createQuery(
            $queryString,
            SMWQueryProcessor::getProcessedParams($parameters, $printouts),
            QueryContext::SPECIAL_PAGE,
            'count',
            $printouts
        );
        $smwStore = ServicesFactory::getInstance()->getStore();
        return $smwStore->getQueryResult($smwQueryObject);
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return array of SMWDataItem
     */
     public static function getPropertyValues($pageName, $propertyName) {
        $store = StoreFactory::getStore ();
        $title = static::getTitle($pageName);
        $subject = DIWikiPage::newFromTitle ( $title );
        $property = new DIProperty ( $propertyName );
        $values = $store->getPropertyValues ( $subject, $property );
        return $values;
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return String with all property values (commaseparated)
     */
    public static function getPropertyValuesAsString($pageName, $propertyName) {
        $values = static::getPropertyValues($pageName, $propertyName);
        $return = implode ( ', ', $values );
        return $return;
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return String with the first property value
     */
    public static function getPropertyValueAsString($pageName, $propertyName) {
        $value = static::getPropertyValue($pageName, $propertyName);
        if($value) {
            return $value .'';
        } else {
            return '';
        }
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return DIWikiPage
     */
    public static function getPropertyValueAsPage($pageName, $propertyName) {
        $values = static::getPropertyValues($pageName, $propertyName);
        if(array_key_exists(0, $values)) {
           return $values[0];
        } else {
            return null;
        }
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return SMWDataItem
     */
    public static function getPropertyValue($pageName, $propertyName) {
        $values = static::getPropertyValues($pageName, $propertyName);
        if(! $values) {
            return null;
        } else if(count($values) > 0) {
           return $values[0];
        } else {
            return null;
        }
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return String of the form 31.12.2001
     */
    public static function getPropertyValueAsDateString($pageName, $propertyName) {
        $dt = static::getPropertyValueAsDateTime($pageName, $propertyName);
        if($dt) {
            return $dt->format('d.m.Y');
        } else {
            return null;
        }
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return String of the form 31.12.2001 19:55:37
     */
    public static function getPropertyValueAsDateTimeString($pageName, $propertyName) {
        $dt = static::getPropertyValueAsDateTime($pageName, $propertyName);
        if($dt) {
            return $dt->format('d.m.Y H:i:s');
        } else {
            return null;
        }
    }

    /**
     * @param Title|String $pageName
     * @param String $propertyName
     * @return DateTime
     */
    public static function getPropertyValueAsDateTime($pageName, $propertyName) {
        $dateDI = static::getPropertyValue($pageName, $propertyName);
        if(!$dateDI) {
            return null;
        } else {
            /** @var SMWDITime $dateDI */
            return $dateDI->asDateTime();
        }
    }

    /**
     * @param Title|String $pageName
     * @return Title of the item's direct category, the first one without spaces, if more than one exists
     */
    public static function getCategory($pageName) {
        global $fsgCategoriesToShowInTitle;

        $categories = static::getCategories($pageName);
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                if (in_array($category->getPrefixedText(), $fsgCategoriesToShowInTitle)) {
                    return $category;
                }
            }
            return $categories[0];
        } else {
            return null;
        }
    }

    /**
     * @param Title|String $pageName
     * @return array of Title objecst of direct categories of this page. results will include hidden categories
     */
    public static function getCategories($pageName) {
        $title = static::getTitle($pageName);
        
        $page = new Article($title);
        $categoriesIterator = $page->getPage()->getCategories();

        $categories = array();
        foreach ($categoriesIterator as $categoryTitle) {
            $categories[] = $categoryTitle;
        }
        return $categories;
    }

    /**
     * @param Title|String $pageName
     * @param String $category, e.g. "Inventarblatt"
     * @return boolean     true iff the page is directly in this category
     */
    public static function isInCategory($pageName, $category) {
        $categories = self::getCategories($pageName);
        foreach ($categories as $categoryTitle) {
            if ($categoryTitle->getText() === $category) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param String $propertyName has the form "path.to.value" or "propertyName"
     * @return PrintRequest object with the given property name and label
     */
    public static function newPropertyPrintRequest($propertyName) {
        if( strpos($propertyName, '.') !== false ) {
            # pattern = "path.to.value"
            $prop = new PropertyChainValue();
            $prop->setUserValue($propertyName);
            return new PrintRequest(PrintRequest::PRINT_CHAIN, $propertyName, $prop);
        } else {
            # pattern = "propertyName"
            $prop = DataValueFactory::getInstance()->newPropertyValueByLabel($propertyName);
            return new PrintRequest(PrintRequest::PRINT_PROP, $propertyName, $prop);
        }
    }

    /**
     * Get the value of a query constraint
     *
     * @param QueryResult $queryResult
     *                 the query result
     * @param $attributeName
     *                 the name of the attribute formulating the constraint
     * @return boolean
     *                 if the boolean attribute has a value, return true otherwise false.
     */
    public static function getBooleanQueryCondition(QueryResult $queryResult, $attributeName) {
        $queryString = $queryResult->getQuery()->getQueryString();
        preg_match ( '/\[\[' . $attributeName . '::([^\]]+)\]\]/', $queryString, $matches );
        if (array_key_exists ( 1, $matches )) {
            $parameterString = $matches[1];
        } else {
            // default
            $parameterString = 'ja';
        }
        return static::isTrue($parameterString);
    }

    /**
     * @param String $inputString
     * @return boolean if the inputString looks like a positive boolean value in DE or EN
     */
    private static function isTrue($inputString) {
        if(strcasecmp( $inputString, 'ja' ) === 0 ||
            strcasecmp( $inputString, 'wahr' ) === 0 ||
            strcasecmp( $inputString, 'yes' ) === 0 ||
            strcasecmp( $inputString, 'true' ) === 0 ||
            strcasecmp( $inputString, '1' ) === 0) {
                return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param Title|String $pageName
     * @return Title
     */
    private static function getTitle($pageName) {
        if($pageName instanceof Title) {
           return $pageName;
       } else {
           return Title::newFromText ( $pageName );
       }
    }

    /**
     * 
     * @param String|Title $page
     * @return string the page's displayTitle
     */
    public static function getDisplayTitle( $page ) {
        $title = self::getTitle($page);

        $titleProperty = new DIProperty( DIProperty::TYPE_DISPLAYTITLE );
        $store = StoreFactory::getStore();

        $subject = SMWDIWikiPage::newFromTitle( $title );
        $values = $store->getPropertyValues( $subject, $titleProperty );
        $first = reset( $values );
        if ($first !== false) {
            return  $first->getString();
        } else {
            return $page;
        }
    }
}