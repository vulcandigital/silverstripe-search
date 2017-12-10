## silverstripe-search
This module is a full text search replacement concept for SilverStripe that enables the use of tanks

![Preview](docs/images/example.jpg)

## Requirements
* silverstripe/framework: ^4.0

## Installation
```sh
composer require vulcandigital/silverstripe-search
```

## Getting Started
"What becomes searchable" would be the biggest question, and the answer is:
> Any `DataObject` or subclass that has the `SearchIndexExtension` enabled

### 1. Apply the extension
To apply the extension to the DataObject add the following configuration properties:

```php
class Recipe extends DataObject
{
    // Apply the extension
    private static $extensions = [
        \Vulcan\Search\Extensions\SearchIndexExtension::class
    ];
    
    // the search tank that records from the class are indexed under 
    // this is optional and will default to "Main" if not provided
    private static $search_tank = 'Recipes'
    
    // The search filter name is used when displaying the filter map on the front end
    // if not provided, it will default to the static class name
    private static $search_filter_name = 'Recipes';
    
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
        'SomeRandomFieldWithContent' => 'Text',
        'UselessField' => 'Boolean'
    ];
    
    // Showing the use of Dot Notation in searchableColumns()
    private static $has_one = [
        'User' => Member::class
    ];
    
    // This will conslidate the content from all columns into a single searchable line of text
    public function searchableColumns() 
    {
        return [
            'Title',
            'Content',
            'SomeRandomFieldWithContent',
            'User.FirstName'
        ]
    }
}
```
> Note: Currently does not support Dot Notation on has_many or many_many relationships

Afterwards, make sure you **dev/build and ?flush**.

#### 2. Build the index
If you have just applied the extension to a DataObject or Page _with_ existing records, you should then run the [Build Search Index Manifest](src/Tasks/BuildIndex.php) task from dev/tasks to index existing records

As new records are added to a `DataObject` they will also be indexed. When a record is deleted, the index entry will be deleted also

If the `DataObject` is `Page`, new records will only be indexed if the page is unpublished, and unindexed when the page is unpublished or deleted 

### 3. Create the search page
Open up the CMS, and create a new **SearchPage**. This can be a root item or a child of any other page

1. Switch to the "Search" tab
2. Select the appropriate tank for the page (default is "Main")
3. Save & Publish the page
4. Begin searching for results within that tank

If you would like to have multiple search pages where the records being searched differ, then you need to specify a unique index tank name for that DataObject (by default, the `Main` tank will be used):

```php
private static $search_tank = 'MyCustomTank';
```

This would ensure all records within that DataObject are stored under a custom tank identifier where that tank can be assigned to a particular `SearchPage`.

This module ships within a default [SearchPage](src/Pages/SearchPage.php) page type. The provided template is for example only and you should create your own override for it. 

## Result Item Rendering
You may want to render your results differently based on what classes they represent (as in the preview image above).

In order to do this for the above `Recipe` class you must create the following folder structure in your theme directory:

```
- themes/
  - mytheme/
    - templates/
      - Vulcan/
        - Search/
          - Render/
```

Then within that folder you can create a new template called `RenderRecipe.ss`. That template is passed (at a top level) complete access to everything in a single result:

```twig
<div class='search-result recipe'>
    <h1><% if $Link %><a href="$Link">$Title</a><% else %>$Title<% end_if %></h1>
    <div class='content'>$Content</div>
    <div class='meta'>Uploaded by: $User.FirstName</div>
</div>
```

## FAQ
**I cannot see existing records for my class in the search**  
Solution 1. `?flush=1`  
Solution 2. If you have applied the extension to a class that already has existing records, you should then run the [Build Search Index Manifest](src/Tasks/BuildIndex.php) task from dev/tasks

**I'm seeing results for records that no longer exist**  
This should not occur, however if it does. You can run the [Search Index Maintenance](src/Tasks/IndexMaintenance.php) task from dev/tasks

## License
[BSD 3-Clause](LICENSE.md) © Vulcan Digital Ltd


