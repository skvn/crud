# Form

Form describes the set of the fields used to build the form interface.

Form can be a plain one level list array, as well as a multidimensional array for a tabbed interface.

In case of tabbed interface, `form_tabbed` config property should be set to `true`. Also a `tabs` array should be defined. The tabbed form is described [below](#tabbed_form_definition)

<a name="form_definition"></a>
## Form definition

Form can be defined either in the global scope or in the list scope.
When building the form, the CRUD package first looks in current scope; if no form found, the global context is taken.

Global scope example:


```
    //config/crud/crud_article.php
    return [
        "name" => "Article",
        "title_field" => "title",
        ...
        "form" => ["title", "translit_title", "small_info", "info", "is_advert"],
```


List context example:


```
    //config/crud/crud_article.php
    return [
        "name" => "Article",
        "title_field" => "title",
        ....
        "list" => [
        	"default" => [
                "title" => "Articles",
                "description" => "Articles main list",
                "form" => ["title", "translit_title", "small_info", "info", "is_advert"],
            ...
```  

<a name="plain_form_definition"></a>
### Plain form

A plain form is just a list of fields that should be displayed in the UI. The example of such form can be viewed above

<a name="tabbed_form_definition"></a>
### Tabbed form

A tabbed form is more complex interface. 

The main idea is to split a large form into logical steps, using [Bootstrap tabs](!http://getbootstrap.com/javascript/#tabs)

You can define the tabs and a set of fields to show on each tab.

To define a tabbed form you need to:
- set `form_tabbed` property to `true` 
- define a list if tabs
- define `form` array as a multidimensional map

#### Define form tabs

The form tabs are defined via `tabs` property. It can be done in the global scope or in the list scope.

Global scope example:

```
    //config/crud/crud_article.php
    return [
        "name" => "Article",
        "title_field" => "title",
        ...
         "tabs" => [
        	'tab_common' => ['title' => "Common information"],
        	'tab_media' => ['title' => "Media"],
            ...
    	],
```
List  scope example:

```
    //config/crud/crud_article.php
    return [
        "name" => "Article",
        "title_field" => "title",
        ...
         "list" => [
        	"default" => [
                "title" => "Articles",
                "description" => "Articles main list",                
                ....
                 "tabs" => [
                    'tab_common' => ['title' => "Common information"],
                    'tab_media' => ['title' => "Media"],
                    ...
                ],
```
<a name="tab_aliases"></a>
`tabs` Is a multidimensional array. The keys are **tab aliases**, the values are arrays describing tab attributes.

**Tab attributes** can be:
- **title** - the title of the tab
- **acl** - the [acl alias](Config/Acl#aliases) used to restrict acces for a form section


#### Define form as multidimensional tab map

If you are using a tabbed form, and `tabbed_form` is set to `true` as well as `tabs` array is defined, the config `form`  attribute should be defined  as **multidimensional array** where the keys are the [tab aliases](#tab_aliases) and the values are simple arrays of [field names](#field_names). 

Example:

```
    //config/crud/crud_article.php
    return [
        "name" => "Article",
        "title_field" => "title",
        ...
         "tabs" => [
        	'tab_common' => ['title' => "Common information"],
        	'tab_media' => ['title' => "Media"],
            ...
    	],
        "form" => [
        	'tab_common' => ["title", "translit_title", "small_info"]
        	'tab_media' => ['small_image_id', "big_image_id"],
            ...
            ]
            
```