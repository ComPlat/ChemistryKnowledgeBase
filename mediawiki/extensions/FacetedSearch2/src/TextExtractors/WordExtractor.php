<?php

namespace DIQA\FacetedSearch2\TextExtractors;

use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class WordExtractor
{

    public function extractDocument(string $path, string $format = 'Word2007'): string
    {
        // $format: 'Word2007' for .docx, 'MsDoc' for legacy .doc, 'RTF', 'ODText', 'HTML'
        $phpWord = IOFactory::load($path, $format);

        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $el) {
                $text .= $this->collectText($el) . "\n";
            }
        }
        return $this->decodeXmlEntities($text);
    }

    /**
     * Decodes XML entities such as &quot;, &amp;, &lt;, &gt;, &apos;
     * as well as numeric entities (e.g. &#34;, &#x22;) that may appear
     * in text extracted from Word documents.
     */
    private function decodeXmlEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function collectText($el): string
    {
        if ($el instanceof Text) {
            return $el->getText();
        }
        if ($el instanceof TextRun) {
            $s = '';
            foreach ($el->getElements() as $child) {
                $s .= $this->collectText($child);
            }
            return $s;
        }
        // Recurse into containers (tables, cells, lists, etc.)
        if (method_exists($el, 'getElements')) {
            $s = '';
            foreach ($el->getElements() as $child) {
                $s .= $this->collectText($child) . ' ';
            }
            return $s;
        }
        return '';
    }


}