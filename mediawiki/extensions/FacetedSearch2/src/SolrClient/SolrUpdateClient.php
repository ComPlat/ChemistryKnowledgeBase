<?php

namespace DIQA\FacetedSearch2\SolrClient;

use DIQA\FacetedSearch2\FacetedSearchUpdateClient;

use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Update\Document;
use DIQA\FacetedSearch2\Utils\Logger;
use Exception;

class SolrUpdateClient implements FacetedSearchUpdateClient
{

    /**
     * @throws Exception
     */
    public function updateDocuments(... $docs): void
    {

        $xml = join("\n", array_map(fn($d) => $this->serializeAsXml($d), $docs));
        $this->logIfConfigured($xml);
        $this->updateSOLR("<add>$xml</add>");
    }

    /**
     * @throws Exception
     */
    public function deleteDocument(string $id): void
    {
        $xml = "<delete><query>id:$id</query></delete>";
        $this->logIfConfigured($xml);
        $this->updateSOLR($xml);
    }

    /**
     * @throws Exception
     */
    public function clearAllDocuments(): void
    {
        $xml = "<delete><query>*:*</query></delete>";
        $this->logIfConfigured($xml);
        $this->updateSOLR($xml);
    }

    private function serializeAsXml(Document $doc): string
    {
        list($wikiPagePropertiesAsXml, $valuePropertiesAsXml) = $this->createPropertyLists($doc);
        $propertiesValuesAsXML = $this->createPropertyValues($doc);
        list($categories, $directCategories) = $this->createCategories($doc);

        $generalFields = [
            $this->createField('id', $doc->getId()),
            $this->createField('smwh_title', $doc->getTitle()),
            $this->createField('smwh_displaytitle', $doc->getDisplayTitle()),
            $this->createField('smwh_namespace_id', $doc->getNamespace()),
            $this->createField('smwh_full_text', $doc->getFulltext()),
            $this->createField('smwh_boost_dummy', $doc->getBoost())

        ];

        $fields = array_merge(
            $generalFields,
            $wikiPagePropertiesAsXml,
            $valuePropertiesAsXml,
            $propertiesValuesAsXML,
            $categories,
            $directCategories
        );

        $fields = array_filter($fields, fn($e) => $e !== '');
        $fields = implode("\n\t", $fields);
        return <<<XML
    <doc>
        $fields
    </doc>
XML;

    }

    private function createField($field, $value): string
    {
        $xml = '';
        if (!is_null($value)) {
            $xml .= "<field name='".$field."'><![CDATA[" . $value . "]]></field>";
        }
        return $xml;
    }
    /**
     * @param Document $doc
     * @return array
     */
    private function createPropertyLists(Document $doc): array
    {
        $wikiPageProperties = array_filter($doc->getPropertyValues(), fn($p) => $p->getProperty()->type == Datatype::WIKIPAGE);
        $wikiPageProperties = array_unique(array_map((fn($p) => Helper::generateSOLRProperty($p->getProperty()->title, $p->getProperty()->type)), $wikiPageProperties));
        $wikiPagePropertiesAsXml = array_map(function ($p) {
            return "<field name='smwh_properties'><![CDATA[{$p}]]></field>";
        }, $wikiPageProperties);


        $valueProperties = array_filter($doc->getPropertyValues(), fn($p) => $p->getProperty()->type !== Datatype::WIKIPAGE);
        $valueProperties = array_unique(array_map((fn($p) => Helper::generateSOLRProperty($p->getProperty()->title, $p->getProperty()->type)), $valueProperties));
        $valuePropertiesAsXml = array_map(function ($p) {
            return "<field name='smwh_attributes'><![CDATA[{$p}]]></field>";
        }, $valueProperties);

        return array($wikiPagePropertiesAsXml, $valuePropertiesAsXml);
    }

    /**
     * @param Document $doc
     * @return string
     */
    private function createPropertyValues(Document $doc): array
    {
        $propertyValues = [];
        foreach ($doc->getPropertyValues() as $pv) {
            $encProperty = Helper::generateSOLRProperty($pv->getProperty()->title, $pv->getProperty()->type);
            foreach ($pv->getValues() as $v) {
                $propertyValues[] = "<field name='$encProperty'><![CDATA[{$v}]]></field>";
            }
            foreach ($pv->getMwTitles() as $v) {
                $propertyValues[] = "<field name='$encProperty'><![CDATA[{$v->getTitle()}|{$v->getDisplayTitle()}]]></field>";
            }
        }
        $dateTimePropertyValues = array_filter($doc->getPropertyValues(), fn($p) => $p->getProperty()->type == Datatype::DATETIME);
        foreach ($dateTimePropertyValues as $pv) {
            $encProperty = Helper::generateSOLRPropertyForSearch($pv->getProperty()->title, $pv->getProperty()->type);
            foreach ($pv->getValues() as $v) {
                $longValueDate = Helper::convertDateTimeToLong($v);
                $propertyValues[] = "<field name='$encProperty'><![CDATA[{$longValueDate}]]></field>";
            }
        }

        return $propertyValues;
    }

    /**
     * @param Document $doc
     * @return array
     */
    private function createCategories(Document $doc): array
    {
        $categoriesAsXML = array_map(function ($c) {
            return "<field name='smwh_categories'><![CDATA[{$c}]]></field>";
        }, $doc->getCategories());

        $directCategoriesAsXML = array_map(function ($c) {
            return "<field name='smwh_directcategories'><![CDATA[{$c}]]></field>";
        }, $doc->getDirectCategories());


        return array($categoriesAsXML, $directCategoriesAsXML);
    }

    private function updateSOLR($xml): void
    {
        try {
            $headerFields = [];
            $headerFields[] = "Content-Type: text/xml";
            $headerFields[] = "Expect:"; // disables 100 CONTINUE
            $headerFields = array_merge($headerFields, Helper::getBasicAuthHeader());
            $url = Helper::getSOLRBaseUrl() . "/update?commit=true&overwrite=true&wt=json";

            $ch = curl_init($url);


            //curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20); //timeout in seconds

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                throw new Exception("Error on request: $error_msg");
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            list($header, $body) = Util::splitResponse($response);
            if ($httpCode >= 200 && $httpCode <= 299) {

                json_decode($body);
                return;

            }
            throw new Exception("Error on update-request. HTTP status: $httpCode. Message: $body");

        } finally {
            curl_close($ch);
        }
    }


    public function initIndex(): bool
    {
        // no impl. for SOLR
        return false;
    }

    public function deleteIndex(): void
    {
        // no impl. for SOLR
    }

    public function existsIndex(): bool
    {
        // no impl. for SOLR
        return false;
    }

    public function refreshIndex(): void
    {
        // no impl. for SOLR
    }

    /**
     * @throws Exception
     */
    private function logIfConfigured(string $xml): void
    {
        global $fs2gDebugMode;
        if ($fs2gDebugMode ?? false) {
            Logger::info("SOLR request: " . $xml);
        }
    }
}
