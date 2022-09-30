<?php
namespace DIQA\ChemExtension\TemplateParser;

abstract class AbstractTemplateNode {

    protected $childNodes;

    /**
     * Template constructor.
     */
    public function __construct()
    {
        $this->childNodes = [];
    }

    public function addNode($node) {
        $this->childNodes[] = $node;
    }

    public function removeNodes(callable $condition) {
        for($i = 0; $i < count($this->childNodes); $i++) {
            $this->childNodes[$i]->removeNodes($condition);
            if ($condition($this->childNodes[$i])) {
                unset($this->childNodes[$i]);
            }
        }
        $this->childNodes = array_values($this->childNodes);
    }
}