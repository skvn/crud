# Relations

<a name="editable"></a> 
## Editable relations. Relation form fields
 

<a name="select"></a> 
### Select



<a name="select_options_providers"></a>
#### Options providers

By default the options for the select form control are build with `CrudModelCollectionBuilder :: create($modelObject)->fetch` method. 
It returns a collection, used to populate select options/


But, there are 2 other options for providing custom data:

- Use `find` property in the field configuration array. In this case, the package would call a method provided in the `find` property on the relation model. This method should return a collection of model instances.

**Example:** Let's say we have an `Article` that is linked with `ArticleType` as many-to-many (BelongsToMany) relation. The field config for such property would be:
```
"types" => [
            "relation" => "belongsToMany",
            "title" => "Article types",
            "model" => "ArticleType",
            "relation_name" => "types",
            "pivot_table" => "article_article_type",
            "pivot_self_key" => "article_id",
            "pivot_foreign_key" => "article_type_id",
            "editable" => 1,
            "type" => "select",
            "find" => "getOnlyActive"
        ],
```   

According to this config, the options of the select control for `types` field should come from `getOnlyActive` method of the `ArticleType` class.

This method should return a [collection](!https://laravel.com/docs/5.2/eloquent-collections)

- Use `method_options` attribute. If you define a `method_options` attribute in the field configuration array (or set it  via using Wizard). The package would look for the method name provided in this option on the `self` model. These method should  also return a collection.

**Example:** Let's look at the same set of models.  We have an `Article` that is linked with `ArticleType` as many-to-many (BelongsToMany) relation. The field config is :
```
"types" => [
            "relation" => "belongsToMany",
            "title" => "Article types",
            "model" => "ArticleType",
            "relation_name" => "types",
            "pivot_table" => "article_article_type",
            "pivot_self_key" => "article_id",
            "pivot_foreign_key" => "article_type_id",
            "editable" => 1,
            "type" => "select",
            "method_options" => "getSuitableTypes"
        ],
```   

According to this config, the options of the select control for `types` field should come from `getSuitableTypes` method of the self `Article` class. The method should return a [collection](!https://laravel.com/docs/5.2/eloquent-collections)


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

By default the options for the tree form control are build with `CrudModelCollectionBuilder :: createTree` method. 
It returns a json ready to use by [JSTree](!https://github.com/vakata/jstree) plugin, which is used in the package.  


But, there are 2 other options for providing JSON:
- Use `find` property in the field configuration array. In this case, the package would call a method provided in `find` property on the relation model. This accepts the property name as the first param, and an array of relation IDs as the second parameter.

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

This method will va called with  two  parameter,  the fist one is the name of the field `categories`, you should use this parameter to name the  ids of the tree options: `categories-1`,`categories-12`, etc  ; and the second one  is an array of `Category` IDs, already linked to the current `Article`. You should use these IDs to set `selected` attributes of the tree options.

**The example of the json data source** should look like this:\
```
[  
   {  
      "text":"First Level",
      "id":"categories-1",
      "parent":"#"
   },
   {  
      "text":"Second-level",
      "id":"categories-2",
      "parent":"categories-1"
   },
   {  
      "text":"Thirrd level",
      "id":"categories-23",
      "parent":"categories-2",
      "state":{  
         "selected":true
      }
   }   
]   
   
```



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
  
