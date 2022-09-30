<?php
namespace DIQA\ChemExtension\TemplateParser;

class TemplateTextNode extends AbstractTemplateNode
{

    private $text;

    /**
     * TemplateText constructor.
     * @param $text
     */
    public function __construct($text)
    {
        parent::__construct();
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    public function serialize(): string
    {
        return $this->text;;
    }
}