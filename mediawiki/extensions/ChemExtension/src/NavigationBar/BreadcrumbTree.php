<?php

namespace DIQA\ChemExtension\NavigationBar;

use OOUI\Tag;
use Title;

class BreadcrumbTree
{

    private $title;
    private $tree;

    /**
     * BreadcrumTree constructor.
     * @param $title For which breadcrumb should be generated
     */
    public function __construct($title)
    {
        $this->title = $title;
        $paths = [];
        $this->getPaths($this->title->getParentCategoryTree(), $paths);
        $reversePaths = array_map(function ($e) {
            return array_reverse($e);
        }, $paths);
        $this->tree = $this->buildTreeFromPaths($reversePaths, $this->title);
    }

    public function getTree(): TreeNode
    {
        return $this->tree;
    }

    private function getPaths($categories, &$allPaths, $path = [])
    {
        foreach ($categories as $sub => $super) {
            if (count($super) === 0) {
                // top node
                $allPaths[] = array_merge($path, [$sub]);
            } else {
                $this->getPaths($super, $allPaths, array_merge($path, [$sub]));
            }
        }
    }

    private function buildTreeFromPaths($paths, $title): TreeNode
    {
        $tree = new TreeNode($title);
        foreach ($paths as $path) {
            $node = $tree;
            foreach ($path as $category) {
                $node = $node->addChild(Title::newFromText($category));
            }
        }
        $tree->visitNodes(function($node) {
            if ($node->isLeaf()) {
                $node->addChild($this->title);
            }
        });
        return $tree;
    }


}

class TreeNode {
    private $title;
    private $children;

    /**
     * TreeNode constructor.
     * @param $title
     */
    public function __construct(?Title $title = null)
    {
        $this->title = $title;
        $this->children = [];
    }

    public function addChild(Title $title) {
        $node = array_filter($this->children, function($n) use ($title) {
            return ($n->getTitle()->getArticleID() === $title->getArticleID());
        });
        if (count($node) > 0) {
           return reset($node);
        }
        $newNode = new TreeNode($title);
        $this->children[] = $newNode;
        return $newNode;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function firstChild() : ?TreeNode {
        return count($this->children) > 0 ? reset($this->children) : null;
    }

    public function serialize() {
        $list = new Tag('ul');
        $list->addClasses(['ce-breadcrumb']);
        if ($this->isLeaf()) {
            if ($this->title->isSpecialPage()) {
                return $list;
            }
            $li = $this->createListItem($this->title, 0);
            $list->appendContent($li);
        } else {
            $this->serialize_(0, $list);
        }
        return $list;
    }

    private function serialize_($indentation, Tag $list) {

        foreach($this->children as $c) {
            $li = $this->createListItem($c->getTitle(), $indentation);
            $list->appendContent($li);
            $c->serialize_($indentation + 10, $list);
        }
    }

    public function isLeaf() {
        return count($this->children) === 0;
    }

    public function visitNodes(callable $action) {
        foreach($this->children as $c) {
            $c->visitNodes($action);
            $action($c);
        }
    }


    private function createTitleLink($title): Tag
    {
        $a = new Tag('a');
        $a->appendContent($title->getText());
        $a->setAttributes(['href' => $title->getFullURL()]);
        return $a;
    }

    /**
     * @param $c
     * @param $indentation
     * @return Tag
     */
    private function createListItem($c, $indentation): Tag
    {
        $li = new Tag('li');
        $typeHint = new Tag('span');
        $type = NavigationBar::getCssType($c);
        $li->setAttributes(['style' => "margin-left: {$indentation}px"]);
        $li->appendContent($typeHint->addClasses(["ce-page-type-$type", 'ce-type-hint']));

        $a = $this->createTitleLink($c);
        $li->appendContent($a);
        return $li;
    }

}
