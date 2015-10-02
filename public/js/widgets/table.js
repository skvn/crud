;(function($, crud){
    var columns = [];
    $.widget("crud.crud_list", {
        options: {},
        _create: function()
        {
            //var crud_cols = crud.crudObj.list.columns;
            //if (crud_cols[0]['ctype'] && crud_cols[0]['ctype'] == 'checkbox')
            //{
            //    crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
            //        $(td).html('<input class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
            //    };
            //    $('.crud_table thead tr:first td:first').html('<input class="i-checks" type="checkbox">');
            //} else
            //{
            //    crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
            //
            //        $(td).data('id',cellData);
            //    };
            //}
            //
            //for (var i=0; i<crud_cols.length; i++)
            //{
            //    if (i>0) {
            //        crud_cols[i]["fnCreatedCell"] = function (td, cellData, rowData, row, col)
            //        {
            //            $(td).attr('id',crud_cols[col]['data']+'_'+rowData.id);
            //        };
            //    }
            //
            //    if (crud_cols[i].hint)
            //    {
            //        if (!crud_cols[i].ctype)
            //        {
            //            crud_cols[i].title += ' ' + crud_tooltip_pattern.replace('%s', crud_cols[i].hint['index']).replace('%t', crud_cols[i].hint['default']);
            //        }
            //    }
            //}
            var tbl = this.element;
            $("thead th", this.element).each(function(){
                var c = $(this);
                var col;

                col = {name: c.data('list_name'), data: c.data('list_data'), ctype: c.data('list_ctype')};
                if (columns.length == 0)
                {
                    if (c.data('list_ctype') === "checkbox")
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).html('<input class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
                        }
                        //$('thead tr:first td:first',tbl).html('<input class="i-checks" type="checkbox">');
                    }
                    else
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).data('id',cellData);
                        }
                    }
                }
                else
                {
                    col.fnCreatedCell = function(td, cellData, rowData, row, col){
                        $(td).attr('id', c.data('list_data')+'_'+rowData.id);
                    }
                }
                columns.push(col);
            });

            //console.log(cols);
            var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
            var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
            this.element.dataTable({
                    searching: false,
                    processing: true,
                    serverSide: true,
                    ajax: crud.format_setting("model_list_url", {model: this.element.data('crud_table'), scope: this.element.data('crud_scope')}),
                    //columns: crud_cols,
                    columns: columns,
                    language: {
                        url: "/vendor/crud/js/plugins/dataTables/lang/russian.json"
                    },
                    rowCallback: rowCallBack


            });
            this.element.on( 'draw.dt', function (e, o) {
                init_checkboxes(columns, this.element);
                crud.trigger('crud.content_loaded', {cont: $(e.target)});
            } );
            this.element.on('dblclick', 'tbody>tr', function (){

                if ($(this).parents('table').first().data('crud_noedit') == '1')
                {
                    return;
                }
                crud.init_modal($(this).find('td').first().data('id'));
            })
        }
    });

    function init_checkboxes(cols, cont)
    {
        if (cols[0]['ctype'] && cols[0]['ctype'] == 'checkbox')
        {
            var all_chck = $('<input class="i-checks" type="checkbox">');
            all_chck.on('ifChecked', function ()
            {
                $('tr td', cont).find('input.i-checks').iCheck('check');
            });
            all_chck.on('ifUnchecked', function ()
            {
                $('tr td', cont).find('input.i-checks').iCheck('uncheck');
            });
            $('thead tr:first th:first', cont).removeClass('sorting_asc').html('').append(all_chck.clone(true));
            $('tfoot tr:first th:first', cont).html('').append(all_chck);
        }

        crud.init_ichecks();

    }

})(jQuery, CRUD)