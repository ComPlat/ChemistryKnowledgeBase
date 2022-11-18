<?php

namespace DIQA\ChemExtension\Utils\TemplateParser;

class TemplateNode extends AbstractTemplateNode {

    private $isRoot;
    private $templateName;
    private $templateIndex;

    /**
     * Template constructor.
     * @param $template
     */
    public function __construct($isRoot = false)
    {
        parent::__construct();
        $this->isRoot = $isRoot;
    }

    /**
     * @param mixed $templateName
     */
    public function setTemplateName($templateName): void
    {
        $this->templateName = $templateName;
    }

    /**
     * @return mixed
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * @return mixed
     */
    public function getTemplateIndex()
    {
        return $this->templateIndex;
    }

    /**
     * @param mixed $templateIndex
     */
    public function setTemplateIndex($templateIndex): void
    {
        $this->templateIndex = $templateIndex;
    }


    public function serialize(): string
    {
        $s = $this->isRoot ? '' : '{{' . $this->templateName;
        $s .= $this->getTextContent();
        $s .= $this->isRoot ? '' : '}}';
        return $s;
    }

    public function getTextContent(): string
    {
        return implode('', array_map(function($node) { return $node->serialize();}, $this->childNodes));
    }

}