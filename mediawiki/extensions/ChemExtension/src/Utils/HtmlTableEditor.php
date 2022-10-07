<?php
namespace DIQA\ChemExtension\Utils;

use DOMXPath;
use DOMDocument;

class HtmlTableEditor {

    private $doc;
    private $form;

    public function __construct($tableHtml, $form) {
        $this->doc = new DOMDocument();
        $this->doc->loadHTML($tableHtml);
        $this->form = $form;
    }

    public function addEditButtonsAsFirstColumn() {
        $xpath = new DOMXPath($this->doc);
        $list = $xpath->query('//tr');
        $i = 0;
        foreach($list as $tr) {

            $td = $this->doc->createElement('td');
            $td->setAttribute("class", "experiment-editable-column");
            $editButton = $this->createEditButton($i);
            if ($i > 0) {
                $td->appendChild($editButton);
            }
            $tr->insertBefore($td, $tr->firstChild);
            $i++;
        }
        $node = $this->doc->documentElement
            ->firstChild->firstChild; // ignore html/body

        return $this->doc->saveHTML($node);
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
}