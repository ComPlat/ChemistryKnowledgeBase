# Technical documentation
This documentation provides a high-level overview about the functionality of ChemWiki.
It tries to boil down every feature to a set of classes/scripts which implements it. 
The target audience is the developer who maintains and extends this code base.

## Extensions

* ChemExtension: Provides all functionality regarding chemical formulas, molecules, publications, investigations, etc..
* WikiFarm: Allows creating sub-wikis with restricted access

## ChemExtension

### Schema of site
* Topics
* Publications
* Investigations
* Molecules and molecule collections

### Chemical formulas
Handling chemical formulas in Mediawiki is the main functionality which this extension 
provides. This includes:
* embed molecules to a page
* embed a molecule with R-Groups to a page
* integrate with VisualEditor
* create automatically molecule pages for molecules embedded in pages
* create automatically concrete molecules from molecules with R-Groups
* add automatically molecule metadata from public sources like PubChem

####Concepts
*Molecule key*: It's a unique identifier of the molecule in the wiki. Same molecule key means same molecule.
For concrete molecules it is just the InChI-Key. For molecule collections it's the SMILES string plus the 
rests used in the molecule (sorted by rest number), e.g. 
  
    
    C1CCCCC1r2r3r7

for a molecule with 3 rests R2, R3 and R7. This is because molecule collection have no InChI keys.

Note: If the SMILES string and the rests exceed the length of 255, the key is hashed with MD5.

####Classes
* *ChemForm*: Represents a chemical formula in a wiki page. 
* *ChemFormParser*: Parses chemical formulas from wiki text and returns a list of *ChemForm* instances.

####Repository
Chemical formulas requires some data to be written in database. 
* chem_form table: Store the molecule ID (natural number beginning with 100000), the molecule key and the image data. 
  The molecule ID serves also as wiki title for a molecule.
* chem_form_index: Store the wiki page id and the molecule key

### Chemical formulas with rests
Chemical formulas with rests (RGroups) are kind of molecule templates which 
get instantiated to real molecules by a set of RGroup-bindings. The user authors 
the R-Groups bindings in the VisualEditor and the system then generates the concrete molecules from it.

A formula with rests is also called a "molecule collection" in our terminology.

### Investigations
Besides chemical formulas, investigations are the second main item in ChemExtension which can be embedded in a wikipage.
Technically it is just a table with data. Each row represents one investigation. The user authors them 
also via VisualEditor.


### Literature references
Publication pages usually have literature references. Such references can be embedded in the text via
VisualEditor. On the rendered page, those references are gathered and displayed as reference list 
at the end of the publication.

The references are resolved via https://dx.doi.org/ or https://api.crossref.org/works/, the data is cached
in the wiki. There is a special page which list all relevant data about a literature reference. It is
linked from the reference list.

    Special:Literature?doi=10.1093/ajae/aaq063

### PubChem
PubChem is used to populate molecule pages with molecule metadata (mass, formula, trivialname, ...)
Whenever a molecule page is created, ChemExtension tries to retrieve metadata from PubChem for this molecule.
If the molecule does not exist in PubChem, it tries to retrieve some of the metadata from the MOLFile via RDKit (mass and formula)

### Special pages
* Special page for checking external services are running: *Special:CheckServices*
* Special page for creating publications/topics. Linked via Authoring menu.
* Special page for finding missing items, ie. find where certain templates are used to indicate there are issues.
  *Special:FindMissingItems*
* Special unused molecules, ie. molecule pages which are not referenced anywhere:
  *Special:FindUnusedMolecules*

## Jobs 
Some processes need to be run asynchronously to avoid longer blocking during page-save 
operations. These are:
* Create of molecule pages. There can be a lot of pages which needs to be created on a page
save. The job class is *MoleculePageCreationJob*. It creates exactly one molecule page.
* Creation of concrete molecules from a molecule collection. This can be also a time-consuming operation
because for every molecule a page is created. The job class is *MoleculeImportJob*. It takes a set
of molecule collections with rests and creates for each collection a set of concrete molecules.

## Patches

## External services
ChemExtension needs 2 external services to work properly. The availability can be 
checked on Special:CheckServices.

* *RGroup-Service*

    This service creates concrete molecules from a molecule with R-Group. 
  It turns a series of rows of R-Groups to a set of concrete molecules. The input 
  and output format is MOLFile V3000.
  

* *Molecule render service*

    This service renders a molecule in MOLFile V3000 format as SVG-graphic.
  
[1] RGroup service
    
    POST /api/v1/rgroup/

Body:

    { 
       "mdl": "\n  -INDIGO-10282213252D\n\n  0  0  0  0  ...",
       "rgroups": [
            {
                "R1": "4-OMe-Ph",
                "R2": "ACE"
            },
            {
                "R1": "Ph",
                "R2": "H"
            }
        ]
    }

Response:

    "rgroup": [
            {
                "R1": "4-OMe-Ph",
                "R2": "ACE",
                "mdl": "\n     RDKit          2D\n\n  0  0  0  0  0  0  0 ....  END\n",
                "smiles": "COc1ccc(~[Ru]23(~C(C)=O)(~n4ccccc4-c4ccccn~24)~n2ccccc2-c2ccccn~32)cc1",
                "inchi": "InChI=1S/2C10H8N2.C7H8O.C2H4O.Ru/c2*1-3-7-11-9(5-1)10-6-2-4-8-12-10;1-8-7-5-3-2-4-6-7;1-2-3;/h2*1-8H;2-6H,1H3;2H,1H3;",
                "inchikey": "QVRFPBKPHLUZKC-UHFFFAOYSA-N",
                "molecular_weight": 566.125575436,
                "formula": "C29H28N4O2Ru"
            },
            {
                "R1": "Ph",
                "R2": "H",
                "mdl": "\n     RDKit          2D\n\n  0  0  0  0  0  0  0 ....  END\n",
                "smiles": "...",
                "inchi": "...",
                "inchikey": "...",
                "molecular_weight": ...,
                "formula": "..."
            }
        ]
    }

[2] Molecule render service

    POST /render

Body:

    { 
       "molfile": "..."
    }

Response:

    {
       "svg": "..."
    }