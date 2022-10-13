<?php

namespace DIQA\ChemExtension\TemplateParser;

class TemplateParser
{
    private $text;

    /**
     * TemplateParser constructor.
     * @param $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    public function parse(): TemplateNode
    {
        $root = new TemplateNode(true);
        $this->parse_($root);
        return $root;
    }

    private function parse_(TemplateNode $node, $startIndex = 0)
    {
        $templateIndices = [];
        $textInCurrentNode = '';
        for ($i = $startIndex; $i < strlen($this->text) - 1; $i++) {
            if ($this->isTemplateStart($i)) {
                if (strlen($textInCurrentNode) > 0) {
                    $node->addNode(new TemplateTextNode($textInCurrentNode));
                    $textInCurrentNode = '';
                }
                $newNode = new TemplateNode();
                $templateName = $this->getTemplateName($i + 2);
                $templateIndex = $this->incrementTemplateIndex($templateIndices, $templateName);
                $newNode->setTemplateName($templateName);
                $newNode->setTemplateIndex($templateIndex);
                $index = $this->parse_($newNode, $i+2+strlen($templateName));
                $node->addNode($newNode);
                $i = $index+1;
            } else if ($this->isTemplateEnd($i)) {
                $node->addNode(new TemplateTextNode($textInCurrentNode));
                return $i;
            } else {
                $textInCurrentNode .= $this->text[$i];
            }
        }

    }

    /**
     * @param $i
     * @return bool
     */
    private function isTemplateStart($i): bool
    {
        return $this->text[$i] == '{' && $this->text[$i + 1] == '{';
    }

    /**
     * @param $i
     * @return bool
     */
    private function isTemplateEnd($i): bool
    {
        return $this->text[$i] == '}' && $this->text[$i + 1] == '}';
    }

    private function getTemplateName($i) {
        $res = preg_match("/\w+/", substr($this->text, $i), $matches);
        return $res !== 0 && $res !== false ? $matches[0] : '';
    }

    private function incrementTemplateIndex(array &$templateIndices, $templateName)
    {
        $templateIndices[$templateName] = $templateIndices[$templateName] ?? 1;
        return $templateIndices[$templateName]++;
    }
}