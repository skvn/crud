

## List actions

You can define various actions for the list elements.

Currently supported the following action types:

- `event` 
- `popup`
- `command`

### Popup list action

Common action config:

```
[
    "title" => "Contest fraud chart",    
    "confirm" => 'aa',        
    "class" => "fa fa-line-chart",
    "btn_class" => "btn-danger" 
    "single" => 1,
    "mass" => 1,
],
```
- `title` = The name of the link and a popup window title
- `confirm` = If set a javascript `confirm` will appear before the action
- `class` = Icon class
- `btn_class` = Buttin class (for mass actions)
- `single` = If the action should appear on each list row
- `mass` = If the action show appear as abutton under the list

**Action types:**

- `popup` = Open the link as modal 
- `popup_id` = Set the id for the modal window

- - -

- `command` = Execute a remote call of the given method for the current CrudModel
- - -
- `event` = Invoke the specofied crud event,  can be combined with `popup`
