# ChemExtension
This extension contains all functionality related to handle scientific chemistry publications in MediaWiki. Main
focus lies on displaying and editing of molecules. Additionally, we support authoring of investigations which are 
sets of experiments. They can be entered in a structured way and queried by the system to be displayed on overview pages.
Finally, we support literature references which are a necessity in scientific publications, and we integrate the common 
database "PubChem" to populate the wiki automatically with data about molecules.

## Page types and structure
The extension imposes a particular data model in the wiki which looks as following: 
* *Topics*

  Topics are authored compilations of publications. They are represented as sub-categories of the pre-defined category
  "Topic". The hierarchy of topics builds a tree. It is displayed in the navigation on the left side.


* *Publications*

  Publications (aka papers) are member of at least one topic. They are located in the main namespace. Besides text and images,
  they usually contain molecules and investigations.


* *Investigations*

  Investigations are sets of experiments. An investigation has a certain type and, according to the type, a particular set of
  fields. Technically, an investigation is a subpage of a publication, and an instantiation of a multi-instance form.


* *Molecules and molecule collections*

  Molecule and molecule collection pages are in a separate namespace "Molecule". Each page there represents one molecule
  or one collection of molecules (cf. section "Terms"). They usually contain metadata about the molecule like mass or trivialname.

  
## Namespaces
The extension adds 2 additional namespaces: 
* Molecule 
* and Reaction 

## Chemical formulas
Handling chemical formulas ([Fig. 1]) in Mediawiki is the main functionality which this extension
provides. This includes:
* embed molecules to a page 
* embed a molecule with R-Groups to a page 
* integrate Ketcher molecule editor with VisualEditor
* create automatically molecule pages for molecules embedded in pages
* create and instantiate concrete molecules from molecules with R-Groups
* add automatically molecule metadata from the public source PubChem

Chemical formulas with rests (R-Groups) are kind of molecule templates which
get instantiated to real molecules by a set of RGroup-bindings. The user authors
the R-Groups bindings in the VisualEditor and the system then generates the concrete 
molecules automatically from it. ([Fig. 2])

A molecule with rests is also called a "molecule collection" in our terminology.

### Terms
*Molecule key*: It's a unique identifier of the molecule in the wiki. Same molecule key means same molecule.
For concrete molecules it is just the InChI-Key. For molecule collections it's the SMILES string plus the
rests used in the molecule (sorted by rest number), e.g.


    C1CCCCC1r2r3r7

for a molecule with 3 rests R2, R3 and R7. This is because a molecule collection has no InChI key.

Note: If the SMILES string and the rests exceed the length of 255, the key is hashed with MD5.

*chemFormId*: It's a numeric number starting at 100000. It's used as the wiki title of a molecule. 
This number is an easy and unique identifier for molecules in the wiki. It's synthetic, so different
instances of this wiki will have different numbers for a molecule, unlike the molecule key. 

### Classes
* *ChemForm*: Represents a chemical formula in a wiki page.
* *ChemFormParser*: Parses chemical formulas from wiki text and returns a list of *ChemForm* instances.
Parsing happens during article save operation.

### Repository
Chemical formulas requires some additional data in database. The following tables are used therefore:
* *chem_form*: Stores the chemFormId (primary key), the molecule key and the image data
    * *id*: chemFormId (cf. Terms)
    * *molecule_key*: Molecule key (cf. Terms)
    * *img_data*: SVG-image as base64-encoded


* *chem_form_index*: It contains the information where a particular molecule appears in the wiki 
  (effectively an index for molecules).
    * *id*: primary key
    * *page_id*: Wiki-ID of page a molecule appears on
    * *chem_form_id*: ID of molecule


* *molecule_collection*: Serves as index for molecule collections. Every row represents one concrete molecule.
    * *id*: primary key
    * *publication_page_id*: Wiki-ID of publication page where molecule collection is specified.
    * *molecule_page_id*: Wiki-ID of page of concrete molecule
    * *molecule_collection_page_id*: Wiki-ID of page of molecule collection
    * *molecule_collection_id*: ChemFormId of molecule collection
    * *rgroups*: R-Group-Bindings for this molecule (as JSON string)

The class *ChemFormRepository* abstracts access to these tables.

### Parser functions / Hooks
* *chemform*: Hook for defining a molecule on a page
    * tag content is the molecule as MOLFile V3000
    * inchikey: the inchikey (if not a molecule collection)
    * inchi: the inchi representation (not really used at the moment)
    * smiles: the SMILES representation
    * width: Width to display in pixels
    * height: Height to display in pixels
    * float: left, right or none (default)

  Optionally, one can specify rests with the attributes r1, r2, r3, ... . They contain a comma-separated list
  of molecule rests. Example:

      r1 = "H, Ph, ACE"
      r2 = "Br, H, S"

  This would represent 3 molecules with (R1=H, R2=Br), (R1=Ph, R2=H) and (R1=ACE, R2=S).


* *moleculelink*: Parser function for defining a link to a molecule page. Parameters:
    * *link*: the chemformId of the molecule


* *showMoleculeCollection*: Inserts a table to show all concrete molecules of the current site
  (assuming it is a molecule collection). No parameters

* *extractElements*: Extracts chemical elements from a chemical formula as a comma-separated list. Parameters:
    * *formula*: the chemical (sum-)formula.

### Scripts
* ve.extend.js: Renders custom buttons in the VE dialogs
* ve.insert-commands.js: Registers new commands in "insert" menu of VE
* ve.oo.ui.ketcher-dialog.js: Dialog which includes Ketcher to VE
* ve.oo.ui.ketcher-widget.js: Content of Ketcher dialog
* ve.oo.ui.molecule-rgroups-dialog.js: Dialog for defining R-Groups
* ve.oo.ui.molecule-rgroups-widget.js: Content of R-Group dialog
* ve.oo-ui.inchikey-lookup.js: Search widget to find molecules
* oo.ui.show-rgroups-dialog.js: Dialog to show R-Groups of a molecule
* oo.ui.rgroups-display-widget.js: Content of the dialog which shows R-Groups
* render-chemform-tooltip.js: Renders the tooltip when hovering over a formula
* rerender-chemform.js: Triggers a rendering of a formula which has not been rendered yet.
* rgroups.js: default set of R-Groups
* ve.oo-ui.rgroups-lookup: Widget to retrieve available R-Groups from the backend

<figure>
    <img src="https://github.com/ComPlat/ChemistryKnowledgeBase/blob/main/doc/images/chemform-overview.PNG"
         alt="Chemical formula integration">
    <figcaption>[Fig. 1] Data flow when users enters a chemical formula in the wiki.</figcaption>
</figure>

<figure>
    <img src="https://github.com/ComPlat/ChemistryKnowledgeBase/blob/main/doc/images/rGroups-overview.PNG"
         alt="R-Groups integration">
    <figcaption>[Fig. 2] Data flow when users enters R-Groups for a molecule collection in the wiki.</figcaption>
</figure>

## Investigations
An investigation is a set of experiments. Each experiment consists of a couple of property values specific to the particular
type of experiment. These property values are grouped into a subobject.
The set of subobjects is stored on a subpage of the page which contains the investigation.

To edit the investigation the user uses the multi-instance form editor which is part of PageForms-extension. It
stores the experiments as a nested template call.

    {{Photocatalytic_CO2_conversion_experiments|
      experiment=
      {{Photocatalytic_CO2_conversion|cat conc=...|catalyst=...|...}}
      {{Photocatalytic_CO2_conversion|cat conc=...|catalyst=...|...}}
      {{Photocatalytic_CO2_conversion|cat conc=...|catalyst=...|...}}
      ...
    }}

Each row-template creates the subobject to store the data of one experiment in SMW-properties. For each type of investigation,
those 2 templates must exist as well as a couple of properties to hold the data. The row template is supposed
to create an HTML table row. The investigation template has to enclose these rows with an HTML table head/body.

      {{#subobject:
        |Catalyst={{{catalyst|}}}
        |Catalyst concentration={{{cat conc|}}}
      ....
      }}

New investigation types needs to be registered in class *ExperimentRepository*. They are then displayed in the VE-GUI
when adding an investigation to a page. This class contains all data about investigation types, it is not stored in the DB.

The feature is integrated in VisualEditor. The according menu item "Insert->Investigation" 
adds the parser function "#experimentlist" to embed the investigation data in the page. 
It refers to the name (=subpage) and the type of investigation. Refer to the parser function 
section for details.

Additionally, a topic page can include several investigations via the parser function "#experimentlink". This is
also editable by VisualEditor ("Insert->Investigation link"). Refer to the parser function section for details.

### Classes
* ExperimentListRenderer: renders the *#experimentlist* parser function.
* ExperimentLinkRenderer: renders the *#experimentlink* parser function.
* ExperimentType: DTO class to access the registered type of investigation from *ExperimentRepository*
* ExperimentRepository: stores the registered investigations

### Scripts
* ve.oo-ui.add-experiment-dialog: VE-Dialog for *#experimentlist* parser function
* ve.oo-ui.add-experiment-link-dialog: VE-Dialog for *#experimentlink* parser function
* ve.oo-ui.add-experiment-widget: Widget which is displayed in the VE-Dialog.

### Parser functions
* *experimentlist*: Inserts a new investigation (=list of experiments)
    * *form*: The type of the experiment (effectively the form used)
    * *name*: Arbitrary name of the experiment


* *experimentlink*: Insert existing investigations into a page. It refers per default to all investigations
  of that type.
    * *form*: The type of the experiment (effectively the form used)
    * *restrictToPages*: A SMW query to select the experiments (default is all of the given type)
    
## Navigation bar
On the left side we have a side-bar showing the content of the wiki. The content is context-specific..
There are 4 "tabs": Topics, Publications, Investigations and Molecules. On all tabs you see the current
location as a breadcrumb-tree. Below there is specific content for each tab. The content also depends
on the type of page, the user is currently on.

If the user is on a topic page:
* Topic-tab: On the topic-tab there is the subtree of the current topic
* Publication-tab: Shows all publications under the topic (with search field)
* Investigation-tab: Shows all investigations under the topic (with search field)
* Molecules: Search field for all molecules used under the topic.

If the user is on a publication page
* Topic-tab: On the topic-tab there is the subtree of the topic of the publication (the first one, if multiple)
* Publication-tab: Shows the current publication
* Investigation-tab: Shows all investigations in the publication (with search field)
* Molecules: Search field for all molecules used in this publication.

If the user is on a investigation page:
* Topic: shows subtree of the category the publication of the investigation belongs to.
* Publications: shows all publications
* Investigation: shows nothing
* Molecules: Search field for all molecules used in this investigation.

If the user is on a molecule page:
* Topic: Shows all topics
* Publications: shows all publications
* Investigation: shows nothing
* Molecules: Search field for all molecules.

### Classes
* NavigationBar: Renders the whole content of the navigation bar
* BradcrumbTree: Renders the breadcrumb with the current location
* InvestigationFinder: Retrieves the investigations depending on the context
* InvestigationList: Renders the investigation tab content.
* MoleculeFinder: Retrieves the molecules depending on the context
* MoleculeList: Renders the molecule tab content.
* PublicationList: Renders the publication tab content.

### Scripts
* breadcrumb.js: Contains all javascript for navigation bar

## Literature references
Publication pages usually have literature references. Such references can be embedded in the text via
VisualEditor ("Insert->Literature reference" via "#literature"-parserfunction). On the rendered page, those references 
are displayed with small shortcuts like [CHF20]. They are linked with a reference list at the end of the 
publication. This reference list is automatically generated from the data retrieved by DOI. It is appended automatically
to an article if there are any on the page.

The references are resolved via https://dx.doi.org/ or https://api.crossref.org/works/, the data is cached
in the wiki. There is a special page which list all relevant data about a literature reference. It is
linked from the reference list.

    Special:Literature?doi=10.1093/ajae/aaq063

### Repository
Literature data is cached in the database. Otherwise, it would be necessary to retrieve it
always from the web which is too slow (~1s for each reference). The following table is used:

* *literature*: Caches the literature data
    * *id*: primary key
    * *doi*: Digital object identifier
    * *data*: JSON object with literature data

The data is stored when a DOI is retrieved the first time. It is not supposed to be updated. 

### Classes
* *DOIRenderer*: Displays the references (as reference in the text, at the bottom of a page and as a infobox)
* *DOIResolver*: Resolves as DOI and stores the result in the DB
* *DOITools*: Utility classes
* *LiteratureResolverJob*: To retrieve literature data async.
* *SpecialLiterature*: Special page "Special:Literature" to display literature data in detail.

### Parser functions 
* *literature*: Renders a literature reference in the text
    * *doi*: the DOI of the literature referenced

* *doiinfobox*: Renders an infobox with retrieved data for a DOI. Usually placed at the beginning of a page.
    * *first param* (nameless): the DOI of the literature referenced
    
## PubChem
PubChem is used to populate molecule pages with molecule metadata (mass, formula, trivialname, ...)
Whenever a molecule page is created, ChemExtension tries to retrieve metadata from PubChem for this molecule.
If the molecule does not exist in PubChem, it tries to retrieve some of the metadata from the MOLFile via RDKit (mass and formula)

There is a template "Molecule" which is populated by this data. 

### Repository
Data from PubChem is cached in the DB. Mainly due to performance reasons. We do 3 different
requests for each molecule (record, synonyms, categories). The results of all are stored.

* *pub_chem*: Caches the PubChem data
    * *id*: primary key
    * *molecule_key*: Molecule key (cf. Terms)
    * *record*: JSON object
    * *synonyms*: JSON object
    * *categories*: JSON object

### Classes
* *PubChemClient*: Retrieves data from PubChem.
* *PubChem\*Result* classes: Parses results of PubChem-data (record, synonyms, categories).
* *PubChemRepository*: Provides access to database table
* *PubChemService*: Bundles the retrieving and parsing process. This is the main class for using PubChem data.

## Special pages
* Special page for checking external services are running: 
  * *Special:CheckServices*
* Special page for creating publications/topics. Linked via Authoring menu.
    * *Special:CreateNewPaper*
    * *Special:CreateNewTopic*
    * The GUI is enhanced by javascript in *special.create-topic.js*
* Special page for finding missing items, ie. find where certain templates are used to indicate there are issues.
  *Special:FindMissingItems*
* Special unused molecules, ie. molecule pages which are not referenced anywhere:
  *Special:FindUnusedMolecules*

## Jobs
Some processes need to be run asynchronously to avoid longer blocking during page-save
operations. These are:
* *MoleculePageCreationJob*. When a page is saved, a molecule page has to be created. Since there
  can be a lot of molecules on a page, this operation needs to be asynchronous. 
  The job class is *MoleculePageCreationJob*. It creates exactly one molecule page.
* *MoleculeImportJob*: This job creates concrete molecules from a molecule collection. This can be also a time-consuming operation
  because for every molecule a page is created. The job class is *MoleculeImportJob*. It takes a set
  of molecule collections with rests and creates for each collection a set of concrete molecules.

## Mediawiki callback classes / initializations
* MultiContentSave: Called when a page is saved. Registered in Setup class
* Setup: Registers js-modules, styles, MW hooks, parser functions.

## Endpoints
The backend provides a couple of REST-endpoints. The functionality is quite self-explanatory. Basically,
the endpoints are CRUD-operations for the newly added database tables.
All endpoints are located in the Endpoints-package.
The javascript client to easily access those endpoints is located at *client-ajax-endpoints.js*

## Misc
The util package contains a bunch of utility classes with static functions. However, two classes are no utility 
classes and therefore notable:
* *TemplateParser*:  builds an AST for nested template calls. This is necessary to process investigations
  which make use of multi-instance forms. Then we are able to retrieve single data items which the 
  template is populated with.
* *HtmlTableEditor*: parses an HTML table and allows a couple of operations on it. This is required to post-process 
  generated tables and so to provide additional functionality to the HTML tables which are rendered by the investigation 
  feature. e.g. the expand/collapse-feature.

### Other scripts 
* pf-extensions: copy button in multi-form editor
* ve.oo.model.tools.js: Utility class with helper methods
* ve.oo-ui.initialize: General code required on all pages

## Patches
We have to apply several patches to the MW core and the PageForms extension. Either as a bugfix or
because we need to extend certain functionality.

* category-tree.patch: To allow showing the tree in the navigation bar outside of the main content.
* extend-parser-function-editor.patch: Allows adding functionality in the parser-function edit dialog in VE.
* extend-tag-editor.patch: Allows adding functionality in the tag edit dialog in VE.
* mw_namespaces_search_field.patch: Restrict mediawiki standard search to particular namespaces
* pf_autoAdjustment.patch: Fixes sizing issue with multi-instance form editor
* pf_autocomplete_show_label.patch: Shows a label in the auto-complete instead of MW-Title.
* pf_copyButton.patch: Adds a button to copy a row in the multi-instance form
* pf_preSelectByIndex.patch: Allows pre-selecting a particular row in the multi-instance form.
* pf_select2_firefox.patch: Bugfix for Firefox in select2-impl.
* scrollbug-visualeditor.patch: Bugfix for VE to prevent "shaking" when scrolling to the bottom of the page with an open
  edit dialog for a parser-function.
* sticky-ve-toolbar.patch: makes the toolbar in VE "sticky" to the top when scrolling. otherwise, it disappears.
* ve_FocusableNode.patch: Implements a listener when a parser-function element in VE in clicked.

## Category indexing
For technical reasons we need to materialize all category memberships of a page. That means,
the database does not only contain the direct memberships but also the inferred memberships.

Example: A page is member of category 'Car'. 'Car' is subcategory of 'Motor vehicle'. That means, the
page is actually member of 'Car' *and* 'Motor vehicle'. This information is stored in table *category_index*.

category_index has the following fields:
* id (primary key)
* page_id (foreigen key to page-table)
* category_id (foreign key to page-table)

The repository class is *CategoryIndexRepository*.

## Wiki schema
There is a set of pump-primed pages which is included in all wikis. 

* Categories
  * Molecule
  * Molecule collection
  * Investigation
  * Topic


* Properties
  * BelongsToPublication: Link to the publication page from an investigation
  * BasePageName: Link to a publication page from a subobject in an investigation
  * InchIKey: Stores InChI key for a molecule
  * Smiles: Store SMILES string for a molecule
  * Synonym: Stores synonyms for a molecule
  * IUPACName: Stores IUPAC-Name for a molecule
  * CAS: Stores CAS number for a molecule
  * ContainsElement: Stores all different elements used in a molecule
  * MolecularMass: Stores mass of a molecule
  * MolecularFormula: Stores sum-formula for a molecule
  * Abbreviation: Stores abbreviation of a molecule
  * Trivialname: Stores trivialname of a molecule
  * LogP: ??
  * HasVendors: Are there commercial vendors for this substance?


* Templates
  * BaseTemplate: included in all Publication- and Topic-pages
  * Molecule: Included in all molecule pages
  * MoleculeCollection: Included in all molecule collection pages
  * DoiInfo: Used by doiinfobox-parserfunction to render the infobox
  * DisplayMolecule: Display a molecule link 
  * DisplayNumberNotNull: Shows a number only if not null
  
  
The following templates are placeholders to indicate a problem. All of them have two mandatory
parameters: *date* and *author*. The date-format is arbitrary since it's only used for displaying
purposes for now. They are rendered as kind of notification hints/reminders for content creators. 
  * MissingMolecule
  * FaultyMolecule
  * MissingInvestigation
  * MissingSIData
  * MultipleIssues
  * NoCategory
  * UnreferencedCategory
  * WrongMolecule
  * DOINeeded
  * CitationNeeded
  
Note: this list is not complete. There are more templates and properties used for investigations
(2 templates for each and a bunch of properties). There are also a couple of templates for other 
purposes. But these latter ones are not strictly required by this extension.

## External services
ChemExtension needs 2 external services to work properly. The availability can be
checked on Special:CheckServices. They do not use authentication for now.


* *RGroup-Service*

  This service creates concrete molecules from a molecule with R-Group.
  It turns a series of rows of R-Groups to a set of concrete molecules. The input
  and output format is MOLFile V3000. The technology used is RDKit (https://https://www.rdkit.org/)

  * Service-URL: http://193.196.36.39
  * Client class: *MoleculeRGroupServiceClientImpl*
  * Base URL configuration: $wgMoleculeRGroupServiceUrl


* *Molecule render service*

  This service renders a molecule in MOLFile V3000 format as SVG-graphic. The technology used is
  Ketcher as backend-service (https://lifescience.opensource.epam.com/ketcher/index.html)

  * Service-URL: https://dev.ketchersvc.hydrogen.scc.kit.edu
  * Client class: *MoleculeRendererClientImpl*
  * Base URL configuration: $moleculeRendererServiceUrl

### R-Group services
#### Build molecules from molecule template

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

#### Get all available R-Groups:

    GET /api/v1/superatoms/keys

Response:

    {
    "keys": [
        "Br-",
        "Cl-",
        "PF6-",
        "K+",
        "NBu4+",
        "CO",
        "CO2",
        "NCMe",
        .....
      ]
    }

#### Get molecule metadata:

    POST /api/v1/molecules/

Body:

    {
      "mdl": "\n   -INDIGO-01252311222D ....  END\n"
    }

Response:

    {
    "mdl": "\n   -INDIGO-01252311222D ....  END\n",
    "smiles": "c1ccn2~[Ni]34(~Sc2c1)(~Sc1ccccn~31)~n1ccccc1-c1[nH]c2ccccc2n~41",
    "inchi": "...",
    "inchikey": "...",
    "molecular_weight": 475.04353050800006,
    "formula": "C22H19N5NiS2"
}

### Molecule render service
#### Render molecule

    POST /render

Body:

    { 
       "molfile": "\n   -INDIGO-01252311222D ....  END\n"
    }

Response:

    {
       "svg": "<svg version="1.1" xmlns="http://www.w3.org/2000/svg"> ... </svg>"
    }