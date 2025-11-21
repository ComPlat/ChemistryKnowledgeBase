<?php

namespace DIQA\FacetedSearch2;

class CategoryNode
{

    public array $children = [];
    public string $category;
    public ?string $displayTitle;

    public function __construct(string $category, ?string $displayTitle = NULL)
    {
        $this->category = $category;
        $this->displayTitle = $displayTitle;
        $this->children = [];
    }

    public function addChild(CategoryNode $child): void
    {
        $this->children[] = $child;
    }

    static function fromTuples($tuples): CategoryNode
    {
        global $fs2gCategoryTreeRoot;
        $root = new CategoryNode('__ROOT__');
        $configuredTreeRoot = NULL;
        foreach ($tuples as $tuple) {
            $fromNode = self::createOrGetNode($tuple['from'], $tuple['from_displaytitle']);
            if (!is_null($tuple['to'])) {
                $toNode = self::createOrGetNode($tuple['to'], $tuple['to_displaytitle']);
                $toNode->addChild($fromNode);
                if (!is_null($fs2gCategoryTreeRoot) && $fs2gCategoryTreeRoot === $toNode->category) {
                    $configuredTreeRoot = $toNode;
                }
            } else {
                $root->addChild($fromNode);
            }
        }
        return $configuredTreeRoot ?? $root;
    }

    static function createOrGetNode(string $title, ?string $displayTitle): CategoryNode
    {
        static $cache = [];
        if (isset($cache[$title])) {
            $fromNode = $cache[$title];
        } else {
            $fromNode = new CategoryNode($title, $displayTitle);
            $cache[$title] = &$fromNode;
        }
        return $fromNode;
    }
}