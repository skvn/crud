# Relations

<a name="editable"></a> 
## Editable relations. Relation form fields
 
 
<a name="tree"></a> 
### Tree
 
Sometimes it's more convenient to use a tree control instead of plain select. The referenced model could be a tree itself, or you can wish to organize the options into a tree structure. 
 
To do so set the control type to `tree` in the field configuration array.

 ```
 "type" => "tree",
 ```
 Or choose a `tree` option in the wizard drop-down.

<a name="tree_options_providers"></a>
#### Options providers

By default the options for the the tree form control are build with `CrudModelCollectionBuilder :: createTree` method. 
It returns a json ready to use by [JSTree](!https://github.com/vakata/jstree) plugin, which is used in the package.  


But, there are 2 other options for providing JSON:
- Use `find` property in the field configuration array. In this case, the package would call a method provided in `find` property on the relation model. This method should accept an array of relation IDs as the only parameter.

**Example:** Let's say we have an `Article` that is linked with `Category` as many-to-many (BelongsToMany) relation. The field config for such property would be:
```
"categories" => [
            "relation" => "belongsToMany",
            "title" => "Article categories",
            "model" => "category",
            "relation_name" => "categories",
            "pivot_table" => "article_category",
            "pivot_self_key" => "article_id",
            "pivot_foreign_key" => "category_id",
            "editable" => 1,
            "type" => "tree",
            "find" => "getAsTree"
        ],
```   

According to this config, the options of the tree control for `categories` field should come from `getAsTree` method of the `Category` class.

This method should accept one parameter, which is an array of `Category` IDs, already linked to the current `Article`. You should use these IDs to set `selected` attributes of the tree options.

>You can read more about the JSON format for the tree control on the [plugin's documentation page](!https://github.com/vakata/jstree#the-required-json-format)

- Use `method_options` attribute. If you define a `method_options` attribute in the field configuration array (or set it  via using Wizard). The package would look for the method name provided in this option on the `self` model. These method should  also accept one parameter containing relation IDs.

**Example:** Let's look at the same set of models.  We have an `Article` that is linked with `Category` as many-to-many (BelongsToMany) relation. The field config is :
```
"categories" => [
            "relation" => "belongsToMany",
            "title" => "Article categories",
            "model" => "category",
            "relation_name" => "categories",
            "pivot_table" => "article_category",
            "pivot_self_key" => "article_id",
            "pivot_foreign_key" => "category_id",
            "editable" => 1,
            "type" => "tree",
            "method_options" => "getCategoriesAsTree"
        ],
```   

According to this config, the options of the tree control for `categories` field should come from `getCategoriesAsTree` method of the self `Article` class.

>You can read more about the JSON format for the tree control on the [plugin's documentation page](!https://github.com/vakata/jstree#the-required-json-format) 
  
