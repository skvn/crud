<a name="event_handling">
## Event Handling

On the frontend, CRUD package  can listen to some DOM events with the help of data-attributes (directives). 

CRUD package supports several types of events and directives.

**Events:**
- [**click**](#click_events)
- [**change**](#change_events)

**Directives:**
- [**crud_action**](#crud_action)
- [**crud_event**](#crud_event)
- [**crud_popup**](#crud_popup)


<a name="click_events">
## Click event

The `click` event handling is attached via html attribute `data-click`
The following  directives are supported:
- [**crud_action**](#crud_action)
- [**crud_event**](#crud_event)
- [**crud_popup**](#crud_popup)


<a name="crud_popup">
## Crud popup

You can easily open any uri in a [modal](!http://getbootstrap.com/javascript/#modals)  with the `crud_popup` directive.

Let's look at the example:

```
<a href="/admin/utls/show_stat" data-click="crud_popup">Open popup</a>
```
This link will open a [Bootstrap modal](!http://getbootstrap.com/javascript/#modals)  with the content provided by `/admin/utls/show_stat` url.

Alternatively the URL can be defined with `data-uri` attribute
```
<button data-uri="/admin/utls/show_stat" data-click="crud_popup">Open popup</button>
```

Also you can define the ID for the modal div with `data-popup` attribute. The ID can be used to define a [callback](#callbacks_onshow) (the function executed when the modal is rendered)

```
<button data-uri="/admin/utls/show_stat" data-popup="stats_form" data-click="crud_popup">Open popup</button>
```

<a name="crud_actions"></a>
## CRUD Actions

FIXME:Определение, как описывать.

<a name="callbacks"></a>
## Callbacks

FIXME:Определение, как описывать.

<a name="#callbacks_onshow"></a>
### onShow_`ID`

If you set  the ID for your [CRUD popup](#crud_popup) with `data-popup` attribute, you can define a callback for it as a [crud action](#crud_actions) with `onShow_YOUR_POPUP_ID` name. This function will be executed after the popup is rendered.

Example:

FIXME: ДОПИСАТЬ
