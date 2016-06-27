# Model configuration




## Track authors
//TODO
track_authors
  $const = ($op == "create") ? "static::CREATED_BY" : "static::UPDATED_BY";
         $fld = ($op == "create") ? "created_by" : "updated_by";

<a name="audit_trail"></a>
## Audit trail

//TODO

### Configuration attribute
`track_history`

### Possible values

**- null | empty string | not set** - No audit trail will be performed on the model
**- `detail`** - The changes will be tracked as a separate record for a changed field
**- `summary`** - The changes will be tracked as one record for a change. The changed attributes will be combined in a php array and stored in a JSON string.


