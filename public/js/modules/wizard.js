;(function($, crud, win){



    var used_list_aliases = {};
    var relations = [];

    crud.bind('page.start', function(){
        init();
    });



    function init()
    {


        init_steps();


        var crud_actions = {

            wizard_add_list: function (elem) {
                var alias = $.trim($('#list_alias').val());
                var title =  alias;
                if (alias == '')
                {
                    alias = 'default';
                    title = $('input[data-model_name=1]').val() +' list';
                }

                alias = alias.toLowerCase().replace(new RegExp(" ","g"),'_');
                if (!used_list_aliases[alias] && ! $('.scope_container[data-scope='+alias+']').length)
                {
                    used_list_aliases[alias] = 1;
                    var list_html  = $('#list_tpl').html();
                    list_html = list_html.replace(new RegExp("_ALIAS_","g"),alias);
                    list_html = list_html.replace('_TITLE_',title);
                    $(list_html).appendTo('#lists_container');

                    adjust_step_height();
                    $('#list_alias').css('display','inline');
                    init_draggable_fields();

                } else {
                    alert('List alias "'+alias+'" already in use. Use another alias');
                    return false;
                }


            },
            
            wizard_add_list_col: function (elem) {
                crud.action(elem,'clone_fragment');
                adjust_step_height();
            },

            

            wizard_add_relation: function (elem) {
                var type = $('#rtype').val();
                if (type == '')
                {
                    alert('Choose relation type');
                    return;
                }

                $('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment"  data-skip_arr="1" data-fragment="tpl_rel_'+type+'" data-container="r_container"></a>').appendTo($('body')).click().remove();
                adjust_step_height()

            },

            wizard_add_field: function (elem) {
                var type = elem.val();
                if (type == '')
                {

                    $('#f_cancel_'+elem.data('rel')).find('a').click();
                    return;
                }
                var f_html  = $('#tpl_f_'+type).html();
                f_html = f_html.replace(new RegExp("_ALIAS_","g"),elem.data('rel'));
                $('#f_container_'+elem.data('rel')).html(f_html);
                $('#f_cancel_'+elem.data('rel')).show();
                adjust_step_height();


                //$('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment" data-skip_arr="1" data-fragment="tpl_f_'+type+'" data-container="f_container_'+elem.data('rel')+'"></a>').appendTo($('body')).click().remove();

            },

            wizard_add_field_row: function (elem) {

                var fname = '';
                if ($('#field_new').val())
                {
                    fname = $('#field_new').val();

                } else if ($('#field_existing').val())
                {
                    fname = $('#field_existing').val();
                }

                if ($('table[data-rel='+fname+']').length)
                {
                    $('table[data-rel='+fname+']').find('select').first().focus();
                    return;
                }
                if (fname)
                {
                    $.get('/admin/crud_setup/get_field_row/'+fname, function (res) {

                        $('#field_existing option[value='+fname+']').remove();
                        $('#field_existing').val('');
                        $('#field_new').val('');
                        $('#f_container').append(res);
                    });
                }



            },
            
            wizard_add_form_tab: function (elem) {

                var $inp = elem.parent().find('input');
                var $cont = $('#list_form_'+elem.data('rel'));
                if ($inp.val()) {

                    $('<span class="list-group-item list-group-item-danger" data-form_tab="1"><span><span class="fa fa-folder-o"></span> '+$inp.val()+'</span> <div class="pull-right"><a href="#" data-click="crud_action"  data-action="wizard_remove_parent" data-parent="span.list-group-item-danger" data-confirm="Remove tab?" class="label label-danger"><i class="fa fa-trash-o"></i> Remove</a></div></span>').appendTo($cont);
                    $inp.val('');
                    adjust_step_height();
                }
            },

            // wizard_add_filter_field: function (elem) {
            //     var type = elem.val();
            //     var list = elem.data('list');
            //     if (type == '')
            //     {
            //
            //         $('#'+list+'_f_cancel_'+elem.data('rel')).find('a').click();
            //         return;
            //     }
            //     var f_html  = $('#tpl_filter_'+type).html();
            //     f_html = f_html.replace(new RegExp("_ALIAS_","g"),list);
            //     f_html = f_html.replace(new RegExp("_F_","g"),elem.data('rel'));
            //
            //     $('#'+list+'_f_container_'+elem.data('rel')).html(f_html);
            //     $('#'+list+'_f_cancel_'+elem.data('rel')).show();
            //     adjust_step_height()
            //
            // },

            // wizard_remove_field: function (elem)
            // {
            //     elem.parents('table').first().remove();
            //    // $('select.ftype[data-rel='+elem.data('field')+']').val('');
            //     //$('#f_container_'+elem.data('field')).parents('table').first().remove();
            //     //$('#f_cancel_'+elem.data('field')).hide();
            //     adjust_step_height()
            //
            // },

            wizard_adjust_prop_name: function (elem)
            {
                var parent_table = elem.parents('table[data-rel]').first();
                console.log(parent_table);
                var new_name = elem.val();
                if (parent_table.length)
                {
                    parent_table.attr('data-rel', new_name);
                    parent_table.data('rel', new_name);
                    parent_table.find('tr:first').attr('data-rel', new_name).data('rel', new_name).find('td:first').html('<b>'+new_name+'</b>');
                }
            },

            wizard_remove_parent: function (elem)
            {
                if (elem.data('parent'))
                {
                    elem.parents(elem.data('parent')).first().remove();    
                } else {
                    elem.parent().remove();
                }   
                
                adjust_step_height()

            },

            wizard_fill_column_title: function (elem) {
                var parent = elem.parents('tr').first();
                parent.find('input[data-rel=title]').val(elem.find('option:selected').data('title'));

            },

            wizard_remove_filter_field: function (elem)
            {
                var list = elem.data('list');
                $('select.ftype[data-rel='+elem.data('field')+'][data-list='+list+']').val('');
                $('#'+list+'_f_container_'+elem.data('field')).html('');
                $('#'+list+'_f_cancel_'+elem.data('field')).hide();
                adjust_step_height()
            },

            wizard_remove_list_col: function (elem)
            {
                elem.parents('div[data-colidx]').first().remove()
                adjust_step_height()
            },


            wizard_change_relation_model: function (elem)
            {

                if (elem.val()) {
                    var $parent = elem.parents('table').first();
                    var $target = $parent.find('select[data-attr=ref_column]');

                    if ($target.length) {
                        var fields = win.models[elem.val()];

                        if (fields)
                        {
                            var options = "<option value=''>Choose relation  key</option>";
                            for (var i in fields)
                            {
                                if (fields[i] != 'id') {
                                    options += "<option value='" + fields[i] + "'>" + fields[i] + "</option>";
                                }
                            }

                            $target.html(options);
                            $target.focus();
                        }
                    }
                    
                    //fill dp
                    $.get('/admin/crud_setup/wizard/getAvailableSelectOptionsProviders',{args:[elem.val()]}, function(fields) {

                        var options = "<option value=''>No method</option>";
                        for (var i in fields) {
                            options += "<option value='" + fields[i]['name'] + "'>" + fields[i]['name'] + "("+fields[i]['description'] +")</option>";
                        }

                        $parent.find('select[data-rel=dp_method]').html(options);
                    });

                }
            },

            wizard_remove_parent_div: function (elem) {

                elem.parents('div').first().remove();
                adjust_step_height()
            },
            wizard_remove_relation: function (elem) {

                elem.parents('div[data-relation]').first().remove();
                adjust_step_height()
            },

            wizard_sort_options_popup: function (elem)
            {
                var model = '';
                if (elem.data('model'))
                {
                    model = elem.data('model');
                } else if (elem.data('model_field')) {
                    model = $(elem.data('model_field')).val();
                }


                if (model != '')
                {
                    $.get('/admin/crud_setup/model_cols/'+model, function(fields) {

                        var options = "<option value=''>Choose column</option>";
                        for (var i in fields)
                        {
                            options += "<option value='" + fields[i] + "'>" + fields[i] + "</option>";
                        }

                        $('#sort_options').find('select[data-rel=column]').html(options);

                        var $cop = $(elem.data('current_options_container'));
                        if ($cop.val())
                        {
                            var existing = JSON.parse($cop.val());
                            for (var i in existing )
                            {
                                var row = $('#sort_options_tpl').clone().attr('id','');
                                row.attr('data-added', 1);
                                row.find('select').eq(0).val(i);
                                row.find('select').eq(1).val(existing[i]);
                                row.appendTo($('#sort_options_container')).show();
                            }
                        }
                        $('#sort_options').attr('data-cop_container',elem.data('current_options_container'));
                        $('#sort_options').attr('data-display_container', elem.data('display_container'));

                        $('#sort_options').modal({keyboard: false, show: true, backdrop: 'static'});
                        
                    }, 'json');
                }
            },

            wizard_sort_options_save: function (elem)
            {

                var $cop = $($('#sort_options').attr('data-cop_container'));
                var $disp = $($('#sort_options').attr('data-display_container'));

                var $cols = $('#sort_options').find('select[name="popup_sort_option_col[]"]');
                var $orders = $('#sort_options').find('select[name="popup_sort_option_order[]"]');
                var sort_options = {};
                $disp.html('');
                $cols.each(function (i) {

                    var col = $(this).val();
                    if (col)
                    {
                        var order = $orders.eq(i).val();
                        sort_options[col] = order;
                        $disp.append($('<li>'+col+':'+order+'</li>'));

                    }
                });
                if (sort_options) {
                    $cop.val(JSON.stringify(sort_options));
                }
                $('#sort_options').modal('hide');
            },
            
            wizard_toggle_rel_pivot: function (elem) {

                if (elem.prop('checked'))
                {
                    if (elem.val() == '1')
                    {
                        $('#'+elem.data('pivot')).show();
                    } else {
                        $('#'+elem.data('pivot')).hide();
                    }
                }
            },


            wizard_change_pivot_table: function (elem)
                {

                    if (elem.val()) {

                        $.get('/admin/crud_setup/table_cols/'+elem.val(), function(res) {

                            var fields = res;
                            var $target = elem.parent().find('select[data-attr=pivot_column]');
                            if ($target.length) {
                                $target.each (function () {

                                    var options = "<option value=''>Choose pivot "+$(this).data('rel')+" key</option>";
                                    for (var i in fields)
                                    {
                                        if (fields[i] != 'id') {
                                            options += "<option value='" + fields[i] + "'>" + fields[i] + "</option>";
                                        }
                                    }

                                    $(this).html(options);

                                });
                            }
                        }, 'json');


                    }
                },


        };
        crud.add_actions(crud_actions);
    }

    function init_step_events(stepIndex)
    {


        $('[data-toggle="tooltip"]').tooltip();
        adjust_step_height();
        $('#f_container select').on('change', function () {
            adjust_step_height()
        });

        switch (stepIndex){

            case 1:
                //switch to relations
                $('#r_container').off('change', 'input');
                $('#r_container').on('change', 'input', function () {

                    relations = [];
                    var list_rels = [];

                    $('#r_container').find('*[data-relation]').each(function () {

                        var cont = $(this);

                        cont.find('input[data-attr=name]').on('focus', function (){
                            if ($.trim($(this).val()) == '') {
                                var title_input = cont.find('input[data-attr=title]').first();
                                $(this).val(title_input .val().toLowerCase().replace(new RegExp(" ","g"),'_')).trigger('change');
                            }
                        });


                        var rel = {};

                        cont.find('input[data-attr=name]').each(function () {
                            if ($(this).val()) {
                                var  rel_type = cont.data('relation');
                                console.log(rel_type);
                                rel[rel_type] = $(this).val();
                                if (rel_type == 'hasOne' || rel_type== 'belongsTo') {
                                    list_rels.push($(this).val());
                                }
                                relations.push(rel);
                            }
                        });


                    });

                    if (list_rels.length) {
                        $('span[data-rel=container]').show();
                        $('select[data-rel="list_relation"]').each(function () {
                            $(this).empty();
                            var html = '<option value="">Choose relation</option>';
                            for (var i = 0; i < list_rels.length; i++) {
                                html += '<option value="' + list_rels[i] + '">' + list_rels[i] + '</option>';
                            }
                            $(this).html(html);
                        });
                    } else {
                        $('span[data-rel=container]').hide();
                    }

                });


                break;


            case 2:
                //switch to fields
                //exclude  local keys
                $('#f_container').find('tr').show();

                $('#r_container').find('select[data-attr=local_key]').each(function (){
                    if ($(this).val()) {
                        $('#f_container').find('tr[data-rel=' + $(this).val() + ']').hide();
                    }
                });

                
                $('#field_existing').off('change');
                $('#field_new').off('change');

                $('#field_existing').on('change', function () {
                    if ($(this).val() != '')
                    {
                        $('#field_new').val('');
                        $('#field_new').prop('disabled', true);
                    } else {
                        $('#field_new').prop('disabled', false);
                    }
                });

                $('#field_new').on('change', function () {
                    if ($(this).val() != '')
                    {
                        $('#field_existing').val('');
                        $('#field_existing').prop('disabled', true);
                    } else {
                        $('#field_existing').prop('disabled', false);
                    }
                });

                $('#f_container').sortable({cursor: "move",axis: "y"});
                

                break;

            // case 3:
            //     //switch to files
            //     $('#files_container').find('tr').show();
            //     //exclude local keys
            //     $('#r_container').find('select[data-attr=local_key]').each(function (){
            //         if ($(this).val()) {
            //             $('#files_container').find('tr[data-rel=' + $(this).val() + ']').hide();
            //         }
            //     });
            //
            //     //exclude existing fields
            //     $('#f_container').find('select.ftype').each(function (){
            //         if ($(this).val()) {
            //             $('#files_container').find('tr[data-rel=' + $(this).data('rel') + ']').hide();
            //         }
            //     });
            //
            //     adjust_step_height()
            //
            //
            //     break;

            case 3:

                //switch to lists

                fill_field_selects();

                $('#lists_container').off('change', '*[data-list_col]');

                $('#lists_container').on('change', '*[data-list_col]', function (){

                    var $parent = $(this).parents('td').first();

                    if ($(this).val() != '')
                    {
                        $parent.find('*[data-list_col]').prop('disabled', true);

                        $(this).prop('disabled', false);
                        if ($(this).data('rel') == 'list_relation_attr')
                        {
                            $parent.find('*[data-rel=list_relation]').prop('disabled', false);
                        }
                        if ($(this).data('rel') == 'list_relation')
                        {
                            var $rel_attr = $parent.find('*[data-rel=list_relation_attr]');
                            $rel_attr.prop('disabled', false);
                            // $.get('/admin/crud_setup/rel_attr/'+$('input[data-model_name]').val()+'/'+$(this).val(), function(res) {
                            //
                            //         var fields = res;
                            //
                            //         //var options = "<option value=''>Choose pivot " + $(this).data('rel') + " key</option>";
                            //         //for (var i in fields) {
                            //         //    if (fields[i] != 'id') {
                            //         //        options += "<option value='" + fields[i] + "'>" + fields[i] + "</option>";
                            //         //    }
                            //         //}
                            //     }
                            // , 'json');
                        }
                    } else {
                        $parent.find('*[data-list_col]').prop('disabled', false);
                    }
                });

                $('#lists_container *[data-list_col]').each (function () {
                    var $parent = $(this).parents('td').first();
                    if ($(this).val() != '')
                    {
                        $parent.find('*[data-list_col]').prop('disabled', true);
                        $(this).prop('disabled', false);
                        if ($(this).data('rel') == 'list_relation_attr')
                        {
                            $parent.find('*[data-rel=list_relation]').prop('disabled', false);
                        }
                    }
                });



                init_draggable_fields();
                update_form_stubs();

                $('*[data-rel=list_cols]').sortable({handle:".drag_cols",cursor: "move",axis: "y"});

                break;


        }

    }

    function init_draggable_fields()
    {
        $('#lists_container div[data-rel=fields_stack]').each (function () {

            $(this).sortable({
                connectWith: "#list_form_"+$(this).data('list'),
                tolerance: 'pointer',
                update: function (event, ui) {
                    update_form_stubs();
                }
            }).disableSelection();

            $("#list_form_"+$(this).data('list')).sortable({
                connectWith: $(this),
                tolerance: 'pointer'
            }).disableSelection();

        });
    }
    function adjust_step_height()
    {
        $(".wizard .content > .title.current").next(".body")
            .each(function () {
                console.log($(this));
                var bodyHeight = $(this).height();
                var padding = $(this).innerHeight() - bodyHeight;
                bodyHeight += (padding+50) ;
                $(this).parent().animate({ height: bodyHeight }, "fast");
            });
    }


    function fill_field_selects()
    {
        var fields = [];

        $('#f_container table[data-rel]').each ( function () {
                var title = $(this).find('input[data-rel=field_title]').val();
                if (typeof title != 'undefined') {
                    fields.push({value: $(this).data('rel'), text: title});
                }
            }
        );

        if (fields.length) {
            $('select[data-rel="field_select"]').each(function () {
                var val = $(this).data('value');

                $(this).empty();
                var html = '<option value="">Choose  field</option>';
                for (var i = 0; i < fields.length; i++) {
                    html += '<option value="' + fields[i]['value'] + '" data-title="'+fields[i]['text']+'">' + fields[i]['text'] + '('+fields[i]['value']+') </option>';
                }
                $(this).html(html);
                $(this).val(val);
            });
        }
    }

    function update_form_stubs()
    {
        $("*[data-rel=fields_form]").each(function () {

            var lg = $(this).find('.list-group-item');
            if (lg.length)
            {
                $(this).find('.ph').hide();
                lg.removeClass('list-group-item-info').addClass('list-group-item-success');
            } else {
                $(this).find('.ph').show();
            }
        });

        $("*[data-rel=fields_stack]").find('.list-group-item').removeClass('list-group-item-success').addClass('list-group-item-info');


    }
    
    function init_steps()
    {

        $("#form").steps({
            bodyTag: "fieldset",
            transitionEffect: "slideLeft",
            enableAllSteps: true,
            showFinishButtonAlways: true,
            labels: {
                finish: "Save model"
            },
            onStepChanging: function (event, currentIndex, newIndex)
            {


                var form = $(this);

                // Clean up if user went backward before
                if (currentIndex < newIndex)
                {
                    // To remove error styles
                    $(".body:eq(" + newIndex + ") label.error", form).remove();
                    $(".body:eq(" + newIndex + ") .error", form).removeClass("error");
                }

                // Disable validation on fields that are disabled or hidden.
                form.validate().settings.ignore = ":disabled,:hidden";

                var errored = form.find('.error');
                if (errored.length)
                {
                    $('html, body').animate({ scrollTop: errored.first().offset().top - 50 }, 500);
                    errored.first().focus();
                }

                // Start validation; Prevent going forward if false
                return form.valid();
            },
            onStepChanged: function (event, currentIndex, priorIndex)
            {
                init_step_events(currentIndex);
            },
            onFinishing: function (event, currentIndex)
            {
                var form = $(this);

                // Disable validation on fields that are disabled.
                // At this point it's recommended to do an overall check (mean ignoring only disabled fields)
                form.validate().settings.ignore = ":disabled,:hidden";

                // Start validation; Prevent form submission if false
                return form.valid();
            },
            onFinished: function (event, currentIndex)
            {
                var form = $(this);
                form.find('input[type=checkbox]').each(function () {
                   if (!$(this).prop('checked'))
                   {
                       $('<input type="hidden" name="'+$(this).attr('name')+'" value="0" />').appendTo(form);
                   }
                });

                var was_error = false;

                $('*[data-rel=fields_form]').each (function () {

                    if ($(this).data('list') != '_ALIAS_')
                    {
                        var $cont = $(this);
                        var tabs = $cont.find('*[data-form_tab]');
                        var tabs_arr = [];

                        if (tabs.length)
                        {
                            var first_child = $cont.children('.list-group-item').get(0);
                            if (!$(first_child).data('form_tab'))
                            {
                                was_error = true;
                                alert("If you are using tabs. The form should start with a one.\nPlease drag the first tab to the very top of the form fields list");
                                return false;
                            }
                            tabs.each(function () {
                                var tab = {};
                                tab.title = $.trim($(this).find('span').text());
                                if ($(this).data('alias'))
                                {
                                    tab.alias = $(this).data('alias');
                                }
                                var tab_fields = $(this).nextUntil('*[data-form_tab]','span');
                                 if (tab_fields.length)
                                 {
                                     tab.fields = tab_fields.map(function (){if ($(this).data('rel')){return $(this).data('rel')}}).toArray();
                                 } else {
                                     tab.fields = [];
                                 }
                                tabs_arr.push(tab);
                            });

                            if (!was_error)
                            {
                                $('<textarea name="list['+$cont.data('list')+'][form_tabs]">'+JSON.stringify(tabs_arr)+'</textarea>').appendTo($cont);
                            }
                        } else {
                            var form = [];
                            $cont.find('*[data-rel]').each (function () {

                                form.push($(this).data('rel'));
                            });
                            $('<input type="hidden" name="list['+$cont.data('list')+'][form]" value="'+form.join(",")+'" />').appendTo($cont);
                        }
                    }

                });


                if (!was_error) {
                    form.submit();
                }
            }
        }).validate(
            {
                invalidHandler: function(event, validator) {
                    if (validator.numberOfInvalids()) {
                        $('html, body').animate({scrollTop: $(validator.errorList[0].element).offset().top - 50}, 500);
                    }
                },
                errorPlacement: function (error, element)
                {
                    element.before(error);
                },
                rules: {

                }
        });

    }



})($, CRUD, window);