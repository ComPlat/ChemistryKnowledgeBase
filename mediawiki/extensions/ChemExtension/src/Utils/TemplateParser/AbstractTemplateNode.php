<?php

namespace DIQA\ChemExtension\Utils\TemplateParser;

abstract class AbstractTemplateNode
{

    protected $childNodes;

    /**
     * Template constructor.
     */
    public function __construct()
    {
        $this->childNodes = [];
    }

    public function addNode($node)
    {
        $this->childNodes[] = $node;
    }

    public function replaceNode($node, $index): void
    {
        $this->childNodes[$index] = $node;
    }

    public function getFirstChild()
    {
        return reset($this->childNodes);
    }

    public function removeNodes(callable $condition)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $this->childNodes[$i]->removeNodes($condition);
            if ($condition($this->childNodes[$i])) {
                unset($this->childNodes[$i]);
            }
        }
        $this->childNodes = array_values($this->childNodes);
    }

    public function visitNodes(callable $action)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $this->childNodes[$i]->visitNodes($action);
            $action($this->childNodes[$i]);
        }
    }

    public function visitTemplateNodesWithName(callable $action, $name, & $index = 0): void
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            if (($this->childNodes[$i] instanceof TemplateNode) && $this->childNodes[$i]->getTemplateName() === $name) {
                $this->childNodes[$i]->visitTemplateNodesWithName($action, $name, $index);
                $action($this->childNodes[$i], $index++);
            }
        }
    }

    public function getFirstNodeOfType($templateName)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            if ($this->childNodes[$i] instanceof TemplateNode)
                if ($this->childNodes[$i]->getTemplateName() === $templateName) {
                    return $this->childNodes[$i];
                } else {
                    $ret = $this->childNodes[$i]->getFirstNodeOfType($templateName);
                    if (!is_null($ret)) {
                        return $ret;
                    }
                }
        }
        return null;
    }

    public function getNonTextNodes() {
        return array_filter($this->childNodes, fn($n) => !($n instanceof TemplateTextNode));
    }
}