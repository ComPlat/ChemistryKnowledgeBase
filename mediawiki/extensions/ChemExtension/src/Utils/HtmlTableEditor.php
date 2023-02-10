<?php

namespace DIQA\ChemExtension\Utils;

use DOMXPath;
use DOMDocument;
use DOMException;

class HtmlTableEditor
{

    private $doc;
    private $form;

    public function __construct($tableHtml, $form)
    {
        $this->doc = new DOMDocument();
        $this->doc->loadHTML(mb_convert_encoding($tableHtml, 'HTML-ENTITIES', 'UTF-8'));
        $this->form = $form;
    }

    public function retainRows($rowIndices)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $toRemove = [];
        $i = 0;
        foreach ($list as $tr) {
            if ($i == 0) {
                $i++;
                continue; // skip header
            }
            if (!in_array($i, $rowIndices)) {
                $toRemove[] = $tr;
            }
            $i++;
        }
        foreach ($toRemove as $tr) {
            try {
                $tr->parentNode->removeChild($tr);
            } catch (DOMException $e) {
            }
        }

    }

    /**
     * Collapses columns which are annotated with property="hidden"
     * Table header content is moved to attribute "about" and content is replaced with "..."
     * Table column content is put in a hidden span
     */
    public function collapseColumns() {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//td');
        $i = 0;
        foreach ($list as $td) {
            $i++;
            $propertyAttribute = $td->getAttribute('property');
            if ($propertyAttribute == '') {
                continue;
            }
            if ($propertyAttribute === 'hidden') {
                $td->setAttribute('class', 'collapsed-column');
            }
        }
        $i = 0;
        $list = $xpath->query('//th');
        foreach ($list as $td) {
            $i++;
            $propertyAttribute = $td->getAttribute('property');
            if ($propertyAttribute == '') {
                continue;
            }
            if ($propertyAttribute === 'hidden') {
                $td->setAttribute('about', $this->getHtmlFromNode($td));
                $td->setAttribute("style", "cursor: pointer;");
                $td->textContent = '...';
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

    public function addLinkAsLastColumn(array $links)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;
        $link = reset($links);
        foreach ($list as $tr) {
            if ($i === 0) {
                $td = $this->doc->createElement('th');
                $text = $this->doc->createTextNode("Publication page");
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
        $a->setAttribute("datatype", $this->form);
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

        return $this->doc->saveHTML($node);
    }
}