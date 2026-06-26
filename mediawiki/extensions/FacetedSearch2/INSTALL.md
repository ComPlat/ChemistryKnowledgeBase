# Faceted Search 2 
 
## Requirements

* PHP 8.3.x or higher
* Composer
* MediaWiki 1.43.x (LTS)
* SMW 6.x (optional)
* Apache SOLR server v8.30+

	You can set up your own Solr server. Please use the SOLR-schema given in:
		solr-config.zip (tested with Solr 8.30)
		
## Installation Instructions 

* Run in MW root folder:

      composer require diqa/faceted-search-2 --no-dev

* OR add the extension to composer-local.json

         {
            "require": {
                "diqa/faceted-search-2": "~1.0"
            }
         }
 
  * and call

        composer update --no-dev

You can EITHER use SOLR or ElasticSearch as the index backend.

### SOLR Installation

* Install SOLR core:
  * We assume SOLR is installed
  * extract solr-8-config.zip into a temp folder
  * if you want to keep "mw", the default as the SOLR core name move the "mw" folder into /var/solr/data/
  * otherwise
  * rename the mw-folder into the preferred name of your index
  * change "mw" into this name in core.properties
  * then move the folder into /var/solr/data/

 
* Add to your LocalSettings.php (adjust the values if different from default):

      # Default values:

      $fs2gBackend = 'solr';          
      $fs2gBackendConfig = [
        'host' => 'localhost',  
        'port' => 8983,         
        'indexName' => 'mw',          
        'user' => '',           
        'pass' => ''           
      ];
      
      wfLoadExtension('FacetedSearch2');
      $smwgEnabledDeferredUpdate = false; // only if SMW installed

* Start the SOLR server 

### ElasticSearch Installation
* Install ElasticSearch according to the instructions on https://www.elastic.co/guide/en/elasticsearch/reference/current/install-elasticsearch.html
* Add to your LocalSettings.php (adjust the values if different from default):

      # Default values:

      $fs2gBackend = 'elastic';          
      $fs2gBackendConfig = [
        'host' => 'localhost',  
        'port' => 9200,         
        'indexName' => 'mw',          
        'user' => 'elastic',           
        'pass' => '',
        'ssl' => true,
        'verify-ssl' => false
      ];
      
      wfLoadExtension('FacetedSearch2');
      $smwgEnabledDeferredUpdate = false; // only if SMW installed

### Update index

* Create the initial index: 
    
      php {wiki-path}/extensions/FacetedSearch2/maintenance/updateIndex.php -v
	    
* OR via composer:

	    cd {wiki-path}/extensions/FacetedSearch2
	    composer run-script update

* Goto page: Special:FacetedSearch2

That's it.

## CHANGE LOG

### v1.0
* Initial release


## Config options

Here is the documentation for all configuration options in the `config` section of `extension.json`:
All options are set in `LocalSettings.php` using the prefix `$fs2g`, e.g. `$fs2gCreateUpdateJob = true;`.

---

### Index Connection

### `Backend`
- **Default:** `"solr"`
- The backend used for indexing. Currently, only `solr` is supported.

### `BackendConfig`
- **Default:** `[]`
- The configuration of the backend. This is specific to the particular backend. Please check the installation instructions.

### `DebugMode`
- **Default:** `false`
- Activates debug mode. **Must not be used in production** — for debugging purposes only. 
If activated, SOLR queries and responses are logged to a file in the "logs"-folder.
Additionally, the SOLR request is echoed in the frontend's POST-requests.

---

### Indexing

### `CreateUpdateJob`
- **Default:** `true`
- If `true`, an asynchronous index update job is created after a page is saved. If `false`, the update is performed synchronously.

### `EnableIncrementalIndexer`
- **Default:** `true`
- If `true`, changed pages are indexed incrementally when they are saved, moved, or deleted. Can be set to `false` during installation when SOLR is not yet running.

### `BlacklistPages`
- **Default:** `[]`
- Pages listed here are excluded from the search index entirely. Specify prefixed page titles, e.g. `Property:Name` or `Category:People`.

### `IndexImageURL`
- **Default:** `false`
- If `true`, the image URL of a page is indexed as a separate attribute (`Diqa import fullpath`)

### `IndexPredefinedProperties`
- **Default:** `true`
- If `true`, SMW's pre-defined properties are indexed as well. Individual properties can still be excluded from facets by setting `[[Ignore as facet::true]]` on the property page.

### `IndexSubobjects`
- **Default:** `true`
- If `true`, SMW subobjects are indexed in addition to full wiki articles.

---

### Clustering configuration

### `NumericPropertyClusters`
- **Default:** `[]`
- Configures grouping (clustering) of numeric facet values into ranges. Each entry maps a property name to a cluster definition.
- **Example:**
```php
$fs2gNumericPropertyClusters['BuildYear'] = [
      'min'        => -9999,
      'max'        => 9999,
      'lowerBound' => 1700,
      'upperBound' => 2030,
      'interval'   => 10
  ];
```

This creates clusters of width 10 from 1700 to 2030, with an overall clamp of −9999 to 9999. `min` and `max` are optional.

### `DateTimePropertyClusters`
- **Default:** `[]`
- Configures grouping of datetime facet values into ranges. Each entry maps a property name to a `min`/`max` boundary.
- **Example:**
```php
$fs2gDateTimePropertyClusters['Released on'] = [
      'min' => '1990-01-01-00:00:00',
      'max' => '2030-12-31-23:59:59'
  ];
```


### `DateTimeZone`
- **Default:** `"Europe/Berlin"`
- Timezone used to offset datetime values, which are stored internally in UTC.

---

### Search UI

### `HitsPerPage`
- **Default:** `10`
- Maximum number of search results displayed per page.

### `DefaultSortOrder`
- **Default:** `"score"`
- The default sort order for search results. Possible values: `score`, `newest`, `oldest`, `ascending`, `descending`.

### `PlaceholderText`
- **Default:** `""`
- Placeholder text shown in the search input box on `Special:Search`.

### `FacetedSearchForMW`
- **Default:** `true`
- If `true`, searches that entered the standard MediaWiki search field are redirected to the faceted search special page.

### `CreateNewPageLink`
- **Default:** `"?action=edit"`
- URL pattern for the "create new page"-link shown when the search term does not match any existing article. The placeholder `{article}` is replaced by the actual article name. The link is appended to the wiki's base URL (e.g. `http://localhost/mediawiki/index.php`).

### `ShowFileInOverlay`
- **Default:** `false`
- If set to an array of file extensions, files of those types are shown in an overlay rather than navigating away. Set to `false` to disable.
- **Example:** `['pdf', 'png']`

### `ShowSolrScore`
- **Default:** `false`
- If `true`, the raw SOLR relevance score is shown as a tooltip on the "Show Details" link of each search result. Useful for debugging relevance.

### `HeaderControlOrder`
- **Default:** `["sortView", "searchView", "saveSearchLink", "createArticleLink"]`
- Defines the display order of controls in the search header area.

### `FacetControlOrder`
- **Default:** `["selectedFacetLabel", "selectedFacetView", "selectedCategoryView", "removeAllFacets", "divider",
                "facetView", "categoryDropDown", "categoryView", "categoryTree"]`
- Defines the display order of controls in the left-hand facet panel.

---

### Search results enhancements


### `AdditionalLinks`
- **Default:** `[]`
- Configures additional links shown below a search result, based on the article's category.
- Available options: 
  - `confirm` true/false. opens a confirmation dialog with OK and Cancel buttons.
  - `openNewTab` opens the URL in a new tab (true) or sends only a POST-request in background (false)
  - If you specify only a URL (1), the defaults for `confirm` is `false` and for `openNewTab` is `true`.
- **Example:**
```php
$fs2gAdditionalLinks['Document'] = [
    'Show items' => '/index.php/{{PAGENAME}}?showItems=true',     (1)
    'Add to cart' => [
        'url' => '/rest.php/some/endpoint',                       (2)
        'confirm' => true, 
        'openNewTab' => false
    ]
];
```
That means: All results belonging to the category `Document` will have a link 
to `/index.php/{{PAGENAME}}?showItems=true` named `[Show items]` that opens a new tab 
and a link to `/rest.php/some/endpoint` named `[Add to cart]` that sends a POST request 
after showing a confirmation dialog.

 

You can use the following magic words in the URL which are automatically expanded and URL-encoded.
- `{{PAGENAME}}`: The wiki page name of the current result
- `{{FULLPAGENAME}}`: The wiki page name of the current result including the namespace (empty for Main-namespace)
- `{{NAMESPACENUMBER}}`: The numeric index of the namespace
- `{{CURRENTUSER}}`: The current username (empty for anonymous users)

Additionally, you can expand SMW property values of the current result with this syntax:
`{SMW:<name of property>}`. In case there are several values for a property, the values are 
comma-separated

- **Example:** Usage of Property `Topic`
```php
$fs2gAdditionalLinks['Document'] = [
    'Show items' => '/index.php/{{PAGENAME}}?topic={SMW:Topic}&openInOverlay=true',
];
```


### `AnnotationsInSnippet`
- **Default:** `[]`
- Maps category names to lists of property names whose values should be shown in the search result snippet for articles belonging to those categories.
- **Example:**
```php
$fs2gAnnotationsInSnippet = [ 'Document' => [ 'Department', 'DocumentType' ] ];
```

---

### Facets

### `ShowCategories`
- **Default:** `true`
- Show or hide the category facet in the facets panel.

### `ShownCategoryFacets`
- **Default:** `[]`
- Restricts which categories appear in the category facet. If empty, all categories are shown.

### `ShownFacets`
- **Default:** `[]`
- Maps category names to the list of property facets that should be shown when that category is selected in the category drop-down. Only the configured facets are displayed.
- **Example:**
```php
$fs2gShownFacets['Person']  = ['Name', 'Country', 'Age'];
$fs2gShownFacets['Company'] = ['CEO', 'City', 'BusinessArea'];
```


### `FacetValueLimit`
- **Default:** `20`
- The maximum number of values shown per facet before a search field is added to the facet.

### `FacetsWithOR`
- **Default:** `[]`
- List of property names for which an "OR" selection dialog is offered, allowing users to select multiple values with OR logic.




### `TagCloudProperty`
- **Default:** `false`
- If set to a property name (of datatype `string`), a tag cloud for that property is displayed under the search bar.

---

### Category Tree

### `ShowCategoryTree`
- **Default:** `false`
- If `true`, a category tree is displayed in the left-hand panel.

### `CategoryTreeRoot`
- **Default:** `""`
- The root category from which the category tree is built. If empty, the tree starts from the top level.

---

### Namespace Options

### `ShowNamespaces`
- **Default:** `true`
- Show or hide the namespace filter UI element.

### `NamespacesToShow`
- **Default:** `[]`
- List of namespace IDs to include in the namespace filter. If empty, all namespaces are shown.

### `ShowEmptyNamespaces`
- **Default:** `false`
- If `true`, namespaces with zero results are still shown in the namespace filter.

### `NamespaceConstraint`
- **Default:** `[]`
- Restricts which namespaces are visible per user group. Maps group names to arrays of namespace IDs. The `user` group is the fallback for users not belonging to any configured group.
- **Example:**
```php
$fs2gNamespaceConstraint['sysop'] = [0, 10, 14]; // Main, Template, Category
```


---

### Search Result Display

### `CategoriesToShowInTitle`
- **Default:** `[]`
- List of categories whose name is appended to the article title in search results.

### `ShowSortOrder`
- **Default:** `true`
- Show or hide the sort order selector in the search UI.

### `ShowArticleProperties`
- **Default:** `true`
- Show or hide the "Article Properties" button displayed under each search result.

### `CategoryFilter`
- **Default:** `[]`
- Configures the category drop-down filter. Entries are of the form `['category' => 'label']`. The first entry is the default selection. An entry with an empty key `''` represents "no category filter".

### `PromotionProperty`
- **Default:** `false`
- Name of a boolean SMW property. Articles where this property is `true` are visually highlighted (promoted) in results. Set to `false` to disable.

### `DemotionProperty`
- **Default:** `false`
- Name of a boolean SMW property. Articles where this property is `true` are visually greyed out (demoted) in results. Set to `false` to disable.

---

### Boosting

### `ActivateBoosting`
- **Default:** `false`
- Enables or disables the boosting feature for search results.

### `DefaultBoost`
- **Default:** `1.0`
- The default boost factor applied to all pages that have no other boost configured.

### `CategoryBoosts`
- **Default:** `[]`
- Boosts pages belonging to specified categories. Do not include the `Category:` prefix.
- **Example:**
```php
$fs2gCategoryBoosts['People'] = 2.0;
```


### `NamespaceBoosts`
- **Default:** `[]`
- Boosts pages in specified namespaces. Use numeric namespace IDs, not names.
- **Example:**
```php
$fs2gNamespaceBoosts[0] = 2.0; // Main namespace
```


### `TemplateBoosts`
- **Default:** `[]`
- Boosts pages that use a specified template.
- **Example:**
```php
$fs2gTemplateBoosts['MyTemplate'] = 2.0;
```


---

### OR-Dialog Property Grouping

### `PropertyGrouping`
- **Default:** `[]`
- Groups values in the OR-dialog into statically defined groups.
- **Example:**
```php
$fs2gPropertyGrouping['Status'] = [
      'Active'   => ['Running', 'Live'],
      'Inactive' => ['Stopped', 'Archived']
  ];
```


### `PropertyGroupingBySeparator`
- **Default:** `[]`
- Groups values in the OR-dialog by splitting them on a separator character. Only 2 levels are supported.
Values without separator character are displayed together in a parentless section.
- **Example:**
```php
$fs2gPropertyGroupingBySeparator['Location'] = '/';
```


### `PropertyGroupingByUrl`
- **Default:** `[]`
- Groups values in the OR-dialog dynamically by fetching group definitions from a wiki GET REST-endpoint. The path is relative to the wiki's base URL (http://server/path/rest.php).
- **Example:**
```php
$fs2gPropertyGroupingByUrl['Region'] = '/endpoint/groupings';
```

