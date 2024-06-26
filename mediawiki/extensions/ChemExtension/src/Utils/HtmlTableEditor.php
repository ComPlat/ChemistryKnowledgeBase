<?php

namespace DIQA\ChemExtension\Utils;

use DOMXPath;
use DOMDocument;
use DOMException;

class HtmlTableEditor
{

    private $doc;
    private $context;

    private $innerTables;

    public function __construct($tableHtml, $context)
    {
        $this->doc = new DOMDocument();
        $this->doc->loadHTML(mb_convert_encoding($tableHtml, 'HTML-ENTITIES', 'UTF-8'));
        $this->context = $context;
        $this->replaceInnerTables();
    }

    private function replaceInnerTables() {
        $this->innerTables = [];
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//table[@inner]');
        $i = 0;
        foreach ($list as $table) {
            $replaceElement = $this->doc->createElement("span", "element_$i");
            $replaceElement->setAttribute('inner', "element_$i");
            $table->parentNode->replaceChild($replaceElement, $table);
            $this->innerTables["element_$i"] = $table;
            $i++;
        }
    }

    private function restoreInnerTables() {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//span[@inner]');
        foreach ($list as $span) {
            $innerValue = $span->getAttribute('inner');
            $span->parentNode->replaceChild($this->innerTables[$innerValue], $span);
        }
        global $wgCEHiddenColumns;
        if ($wgCEHiddenColumns ?? false) {
            $this->collapseColumns();
        }
    }


    /**
     * Collapses columns which are annotated with property="hidden"
     * Table header content is moved to attribute "about" and content is replaced with "..."
     * Table column content is put in a hidden span.
     *
     * Method is idem-potent
     */
    public function collapseColumns()
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//td');
        $i = 0;
        foreach ($list as $td) {
            $i++;
            if ($td->getAttribute('stashed') !== '') continue;
            $td->setAttribute('stashed', $this->getHtmlFromNode($td));
            $propertyAttribute = $td->getAttribute('property');
            if ($propertyAttribute == '') {
                continue;
            }
            if ($propertyAttribute === 'hidden') {
                $td->setAttribute('class', 'collapsed-column');
                $td->textContent = '';
            }
        }

        $list = $xpath->query('//th');
        foreach ($list as $td) {
            if ($td->getAttribute('stashed') !== '') continue;
            $propertyAttribute = $td->getAttribute('property');
            $td->setAttribute('stashed', $this->getHtmlFromNode($td));
            $td->setAttribute('collapsed', $propertyAttribute === 'hidden' ? 'true' : 'false');
            $td->setAttribute('collapsable', 'true');
            if ($propertyAttribute === 'hidden') {
                $td->textContent = '.';
            }
        }
    }

    public function removeOtherColumns($tabName)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//td');
        $toRemove = [];
        foreach ($list as $td) {
            $resourceAttribute = $td->getAttribute('resource');
            if ($resourceAttribute == '') {
                continue;
            }
            $tabs = explode(",", $resourceAttribute);
            if (!in_array($tabName, $tabs)) {
                $toRemove[] = $td;
            }
        }
        $list = $xpath->query('//th');
        foreach ($list as $th) {
            $resourceAttribute = $th->getAttribute('resource');
            if ($resourceAttribute == '') {
                continue;
            }
            $tabs = explode(",", $resourceAttribute);
            if (!in_array($tabName, $tabs)) {
                $toRemove[] = $th;
            }
        }

        $list = $xpath->query('//tr');
        foreach ($list as $tr) {
            foreach ($toRemove as $td) {
                try {
                    $tr->removeChild($td);
                } catch (DOMException $e) {
                }
            }
        }

    }

    public function removeEmptyColumns()
    {
        $xpath = new DOMXPath($this->doc);
        $notEmptyColumns = [];
        $rows = $xpath->query('//tr');
        if (count($rows) === 0) {
            return;
        }
        $numberOfColumns = count($xpath->query('//tr[1]/th'));
        $firstRow = true;
        foreach ($rows as $tr) {
            if ($firstRow) {
                $firstRow = false; // ignore table header row
                continue;
            }
            $columns = $xpath->query('td', $tr);
            $numberOfColumns = count($columns);
            $i = 0;
            foreach ($columns as $td) {
                if (trim($td->nodeValue) != '' && !in_array($i, $notEmptyColumns)) {
                    $notEmptyColumns[] = $i;
                }
                $i++;
            }
        }
        $allColumns = range(0, $numberOfColumns);
        $emptyColumns = array_diff($allColumns, $notEmptyColumns);

        $rows = $xpath->query('//tr');
        foreach ($rows as $tr) {
            $columns = $xpath->query('th|td', $tr);

            $i = 0;
            foreach ($columns as $td) {
                if (in_array($i, $emptyColumns)) {
                    $tr->removeChild($td);
                }
                $i++;
            }
        }
    }

    public function getTabs(): array
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//td[@resource]');
        $allTabs = [];
        foreach ($list as $td) {
            $resourceAttribute = $td->getAttribute('resource');
            if ($resourceAttribute == '') {
                continue;
            }
            $tabs = explode(",", $resourceAttribute);
            $allTabs = array_merge($allTabs, $tabs);
        }
        $uniqueTabs = array_unique($allTabs);
        $uniqueTabs[] = ''; // this is the default tab that contains everything
        return $uniqueTabs;
    }

    public function addEditButtonsAsFirstColumn()
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;
        foreach ($list as $tr) {

            $td = $this->doc->createElement('td');
            $td->setAttribute("class", "experiment-editable-column");
            $editButton = $this->createEditButton($i);
            if ($i > 0) {
                $td->appendChild($editButton);
            }
            $tr->insertBefore($td, $tr->firstChild);
            $i++;
        }

    }

    public function addIndexAsFirstColumn()
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;
        foreach ($list as $tr) {

            $td = $this->doc->createElement('td');
            $textNode = $this->doc->createTextNode("$i.");
            if ($i > 0) {
                $td->appendChild($textNode);
            }
            $tr->insertBefore($td, $tr->firstChild);
            $i++;
        }

    }

    public function addLinkAsLastColumn(array $links)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;
        $link = reset($links);
        foreach ($list as $tr) {
            if ($i === 0) {
                $td = $this->doc->createElement('th');
                $text = $this->doc->createTextNode("lit");
                $td->appendChild($text);
                $tr->appendChild($td);
                $i++;
                continue;
            }
            $td = $this->doc->createElement('td');
            $linkElement = $this->createLink($link['url'], $link['label'], $link['tooltip'] ?? '');
            $td->appendChild($linkElement);
            $tr->appendChild($td);
            $i++;
            $link = next($links);
            if ($link === false) break;
        }

    }

    public function hideAllRowsExceptFirst()
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;

        foreach ($list as $tr) {
            if ($i === 0) {
                $i++;
                continue;
            }
            $tr->setAttribute('style', 'display:none;');
        }
    }

    public function addTableClass($class)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//table');
        foreach ($list as $t) {
            $oldClass = $t->getAttribute('class');
            if (is_null($oldClass)) {
                $t->setAttribute('class', $class);
            } else {
                $t->setAttribute('class', "$oldClass $class");
            }
        }
    }

    public function shortenTable($maxRows) {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;

        foreach ($list as $tr) {
            $i++;
            if ($i > $maxRows) {
                if ($i === $list->count()) {
                    $table = $tr->parentNode;
                    $newRow = $this->createFurtherResultsRow();
                    $table->appendChild($newRow);
                }
                $tr->parentNode->removeChild($tr);
            }
        }

    }

    public function hideTables()
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//table');
        foreach ($list as $t) {
            $t->setAttribute('style', 'display:none;');
        }
    }

    public function getNumberOfRows()
    {
        $xpath = new DOMXPath($this->doc);
        $rows = $xpath->query('//tr');
        return count($rows);
    }

    /**
     * @param int $i
     * @return \DOMElement|false
     */
    private function createEditButton(int $i)
    {
        $a = $this->doc->createElement('span');
        $a->setAttribute("style", "cursor: pointer");
        $a->setAttribute("class", "experiment-editable");
        $a->setAttribute("resource", $i - 1);
        $a->setAttribute("datatype", $this->context['form']);
        $a->setAttribute("about", $this->context['name']);
        $text = $this->doc->createTextNode('(edit)');
        $a->appendChild($text);
        return $a;
    }

    private function createLink(string $url, string $label, string $tooltip)
    {
        $a = $this->doc->createElement('a');
        $a->setAttribute("class", "experiment-link");
        $a->setAttribute("href", $url);
        $a->setAttribute("target", '_blank');
        $a->setAttribute("title", $tooltip);

        $text = $this->doc->createTextNode($label);
        $a->appendChild($text);
        return $a;
    }

    private function getHtmlFromNode($node): string
    {
        $html = '';
        foreach ($node->childNodes as $childNode) {
            $html .= $this->doc->saveHTML($childNode);
        }
        return $html;
    }

    /**
     * @return false|string
     */
    public function toHtml()
    {
        $node = $this->doc->documentElement
            ->firstChild->firstChild; // ignore html/body
        $this->restoreInnerTables();
        return $this->doc->saveHTML($node);
    }

    /**
     * @return \DOMElement|false
     */
    private function createFurtherResultsRow()
    {
        $a = $this->doc->createElement('span');
        $text = $this->doc->createTextNode('further results hidden...');
        $a->appendChild($text);
        $newRow = $this->doc->createElement('tr');
        $newColumn = $this->doc->createElement('td');
        $newColumn->setAttribute('colspan', 2);
        $newColumn->appendChild($a);
        $newRow->appendChild($newColumn);
        return $newRow;
    }
}