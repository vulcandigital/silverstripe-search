## silverstripe-search
This module is a full text search replacement concept for SilverStripe that enables the use of tanks

![Preview](docs/images/example.jpg)

## Requirements
* silverstripe/framework: ^4.0

## Installation
```sh
composer require vulcandigital/silverstripe-search
```

## Instructions
"What becomes searchable" would be the biggest question, and the answer is:
> Any `DataObject` or subclass that has the `SearchIndexExtension` enabled

To apply the extension to the DataObject add the following configuration properties:

```php
class Recipe extends DataObject
{
    // Apply the extension
    private static $extensions = [
        \Vulcan\Search\Extensions\SearchIndexExtension::class
    ];
    
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

## License
[BSD 3-Clause](LICENSE.md) © Vulcan Digital Ltd


