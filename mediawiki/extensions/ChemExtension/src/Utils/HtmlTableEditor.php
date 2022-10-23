<?php

namespace DIQA\ChemExtension\Utils;

use DOMXPath;
use DOMDocument;

class HtmlTableEditor
{

    private $doc;
    private $form;

    public function __construct($tableHtml, $form)
    {
        $this->doc = new DOMDocument();
        $this->doc->loadHTML($tableHtml);
        $this->form = $form;
    }

    public function retainRows($rowIndices) {
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
            } catch(\DOMException $e) {}
        }

    }

    public function removeOtherColumns($tabIndex)
    {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//td');
        $toRemove = [];
        foreach ($list as $td) {
            $tab = $td->getAttribute('resource');
            if ($tab != '' && $tab != 'tab' . $tabIndex) {
                $toRemove[] = $td;
            }
        }
        $list = $xpath->query('//th');
        foreach ($list as $th) {
            $tab = $th->getAttribute('resource');
            if ($tab != '' && $tab != 'tab' . $tabIndex) {
                $toRemove[] = $th;
            }
        }

        $list = $xpath->query('//tr');
        foreach ($list as $tr) {
            foreach ($toRemove as $td) {
                try {
                    $tr->removeChild($td);
                } catch(\DOMException $e) {}
            }
        }

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