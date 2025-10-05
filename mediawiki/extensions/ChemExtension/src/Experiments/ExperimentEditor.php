<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateNode;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;

class ExperimentEditor
{

    private string $wikiText;
    private string $experimentType;
    private TemplateNode $rootNode;
    private TemplateNode $experimentNode;

    public function __construct($wikiText, $experimentType)
    {
        $this->wikiText = $wikiText;
        $this->experimentType = $experimentType;
        $this->parseTemplate();
    }

    private function parseTemplate(): void
    {
        $te = new TemplateParser($this->wikiText);
        $this->rootNode = $te->parse();
        $templateName = str_replace("_", " ", $this->experimentType);
        $this->experimentNode = $this->rootNode->getFirstNodeOfType($templateName);

    }

    public function serialize(): string
    {
        return $this->rootNode->serialize();
    }

    public function setValue(int $row, string $property, string $value): void
    {
        $exp = ExperimentRepository::getInstance()->getExperimentType($this->experimentType);
        $rowTemplateName = str_replace("_", " ", $exp->getRowTemplate());

        $this->experimentNode->visitTemplateNodesWithName(function (TemplateNode $node, $index) use ($row, $property, $value) {

            if ($index !== $row) {
                return;
            }
            $arguments = ParserFunctionParser::parseArgumentsFromString($node->getTextContent());
            $arguments[$property] = $value;
            $newText = ParserFunctionParser::serializeArguments($arguments);
            $node->replaceNode(new TemplateTextNode("\n|$newText\n"), 0);

        }, $rowTemplateName);

    }
}
