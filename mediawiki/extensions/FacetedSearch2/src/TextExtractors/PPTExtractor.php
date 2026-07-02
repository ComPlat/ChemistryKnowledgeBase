<?php

namespace DIQA\FacetedSearch2\TextExtractors;

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Table;

class PPTExtractor
{
    public function extractPptxTextViaLib(string $path): string
    {
        $reader = IOFactory::createReader('PowerPoint2007');
        $pres = $reader->load($path);

        $out = [];
        foreach ($pres->getAllSlides() as $i => $slide) {
            $text = '';
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof RichText) {
                    foreach ($shape->getParagraphs() as $p) {
                        foreach ($p->getRichTextElements() as $el) {
                            $text .= $el->getText();
                        }
                        $text .= "\n";
                    }
                } elseif ($shape instanceof Table) {
                    foreach ($shape->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            foreach ($cell->getParagraphs() as $p) {
                                foreach ($p->getRichTextElements() as $el) {
                                    $text .= $el->getText() ;
                                }
                            }
                            $text .= "\t";
                        }
                        $text .= "\n";
                    }
                }
            }
            $out[$i + 1] = $text;
        }
        return join("\n", array_values($out));
    }

}