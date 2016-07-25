;(function ($, crud){
    bind_events();
    $.widget("crud.editable_table", {
        options: {},
        _create: function(){
            var tbl = $('table', this.element);
            tbl.data('last_added_row', -1);
            tbl.dataTable({
                paging: false,
                searching: false,
                ordering: false,
                info: false,
                language: {
                    url: "/vendor/crud/js/i18n/vendor/dataTables/"+crud.win.CURRENT_LOCALE+".json"
                }
            });
            update_icons(this.element);
        }
    });

    function update_icons(tbl)
    {
        $('a[data-rel=add]', tbl).hide();
        $('a[data-rel=del]', tbl).show();
        $('a[data-rel=add]', $('tr:last', tbl)).show();
        $('a[data-rel=del]', $('tr:last', tbl)).hide();
    }

    function bind_events()
    {
        var actions = {
            editable_table_add_row: function(elem)
            {
                var tbl = elem.parents("table:first");
                var api = tbl.DataTable();
                var r = elem.parents('tr:first').clone();
                var name = $(elem).parents(".row[data-ref]:first").data('ref');
                var newid = parseInt(tbl.data("last_added_row")) - 1;
                tbl.data("last_added_row", newid);

                $("input", r).each(function(){
                    var e = $(this);
                    e.val("");
                    e.attr('name', e.attr('name').replace(new RegExp("^"+name+"\[[0-9-]+\]"), name + '[' + newid + ']'));
                });
                crud.trigger("form.init", {form: r});
                api.row.add(r).draw();
                update_icons(elem.parents("table:first"));

            },
            editable_table_delete_row: function(elem)
            {
                var api = elem.parents("table:first").DataTable();
                api.row(elem.parents('tr:first')).remove().draw();
            }
        };
        crud.add_actions(actions);
    }
})(jQuery, CRUD)