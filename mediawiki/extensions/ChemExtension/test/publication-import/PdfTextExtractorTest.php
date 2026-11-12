<?php

namespace DIQA\ChemExtension\PublicationImport;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class PdfTextExtractorTest extends TestCase
{
    public function testNormalizeCollapsesBlankLinesAndFormFeeds(): void
    {
        $raw = "Title   \r\n\n\n\n  Table 1\t \fPage 2\n\n\n";
        $this->assertSame("Title\n\n  Table 1\nPage 2", PdfTextExtractor::normalize($raw));
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new PdfTextExtractor('pdftotext'))->extract('/nonexistent/file.pdf');
    }

    public function testFailingBinaryThrows(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'pdf');
        try {
            $this->expectException(RuntimeException::class);
            (new PdfTextExtractor('false'))->extract($file);
        } finally {
            unlink($file);
        }
    }
}
