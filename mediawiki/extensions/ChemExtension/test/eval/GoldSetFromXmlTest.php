<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class GoldSetFromXmlTest extends TestCase
{
    private function publicationsXml(): string
    {
        return '<mediawiki><page><title>My Paper</title><revision><text>'
            . 'Intro {{DOI|doi=10.1021/abc123}} [[Category:Photocatalytic CO2 conversion to CO]]'
            . '</text></revision></page></mediawiki>';
    }

    private function investigationsXml(): string
    {
        // real exports use spaces (not underscores) in template names
        $text = "{{Photocatalytic CO2 conversion experiments|experiments={{Photocatalytic CO2 conversion\n"
            . "|catalyst=Molecule:100\n|cat conc=5\n|Turnover_number__CO=12\n|include=true\n}}"
            . "{{Photocatalytic CO2 conversion\n|catalyst=Molecule:200\n|Turnover_number__CO=30\n}}}}";
        return '<mediawiki><page><title>My Paper/Table 1</title><revision><text>'
            . htmlspecialchars($text, ENT_XML1) . '</text></revision></page></mediawiki>';
    }

    public function testBuildLinksDoiTopicAndExperiments(): void
    {
        $entries = GoldSetFromXml::build($this->publicationsXml(), $this->investigationsXml());

        $this->assertCount(1, $entries);
        $e = $entries[0];
        $this->assertSame('10.1021/abc123', $e['doi']);
        $this->assertSame('Photocatalytic_CO2_conversion', $e['topic']);
        $this->assertCount(2, $e['experiments']);
        $this->assertSame('Molecule:100', $e['experiments'][0]['catalyst']);
        $this->assertSame('12', $e['experiments'][0]['Turnover_number__CO']);
        // bookkeeping params are dropped
        $this->assertArrayNotHasKey('include', $e['experiments'][0]);
    }

    public function testParseRowsIgnoresWrapperTemplate(): void
    {
        // the "...experiments" wrapper must not be parsed as a row (space form)
        $text = "{{Photocatalytic CO2 conversion experiments|experiments={{Photocatalytic CO2 conversion\n|catalyst=Molecule:1\n}}}}";
        $rows = GoldSetFromXml::parseRows($text, 'Photocatalytic_CO2_conversion');
        $this->assertCount(1, $rows);
        $this->assertSame('Molecule:1', $rows[0]['catalyst']);
    }

    public function testMoleculeResolverPassesThroughCanonicalIds(): void
    {
        $resolver = new MoleculeResolver();
        $this->assertSame('Molecule:100', $resolver->canonicalize('Molecule:100'));
        $this->assertSame('Molecule:100', $resolver->canonicalize('molecule:100'));
    }
}
