# Ontology Integration
Create a page with name: *MediaWiki:Smw_import_<kürzel>* where *<kürzel>* is the abbreviation for the namespace with this content:

    http://some.url.org/|[http://some.url.org/ Friendly name of this vocabulary]
     trivialname|Type:Text

    [[Category:Imported vocabulary]]

Note: SMW data types can be found here: https://www.semantic-mediawiki.org/wiki/Help:List_of_datatypes

Add on the property page "Property:Trivialname" the following annotation:

    [[Imported from::<kürzel>:trivialname]]

Do an RDF Export of a page using this property (see example below)

## Example
Ontology Page

http://chemwiki.scc.kit.edu/main/mediawiki/index.php?title=MediaWiki:Smw_import_chebi

Content

    http://purl.obolibrary.org/obo/|http://purl.obolibrary.org/obo/ Chemical Entities of Biological Interest]
     CHEBI_35223|Type:Page

    [[Category:Imported vocabulary]]

Property Page 

http://chemwiki.scc.kit.edu/main/mediawiki/Property:Catalyst

Added Content

    [[Imported from::chebi:CHEBI_35223]]
Test
Export Page


http://chemwiki.scc.kit.edu/main/mediawiki/Special:ExportRDF

Content

    Category:Investigation
    Property:Catalyst
    Category:Photocatalytic_CO2_conversion_experiments
    Carbon_dioxide_reduction_via_light_activation_of_a_ruthenium–Ni(cyclam)_complex/Table_1

Result

    [...]
    <owl:ObjectProperty rdf:about="[http://purl.obolibrary.org/obo/CHEBI_35223"/>
    [...]