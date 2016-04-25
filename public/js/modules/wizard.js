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
                if (!used_list_aliases[alias])
                {
                    used_list_aliases[alias] = 1;
                    var list_html  = $('#list_tpl').html();
                    list_html = list_html.replace(new RegExp("_ALIAS_","g"),alias);
                    list_html = list_html.replace('_TITLE_',title);
                    $(list_html).appendTo('#lists_container');

                    adjust_step_height();
                    $('#list_alias').css('display','inline');

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

                        $('#field_existing').val('');
                        $('#field_new').val('');
                        $('#f_container').append(res);
                    });
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
                    var $target = elem.parents('table').first().find('select[data-attr=ref_column]');

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

            case 3:
                //switch to files
                $('#files_container').find('tr').show();
                //exclude local keys
                $('#r_container').find('select[data-attr=local_key]').each(function (){
                    if ($(this).val()) {
                        $('#files_container').find('tr[data-rel=' + $(this).val() + ']').hide();
                    }
                });

                //exclude existing fields
                $('#f_container').find('select.ftype').each(function (){
                    if ($(this).val()) {
                        $('#files_container').find('tr[data-rel=' + $(this).data('rel') + ']').hide();
                    }
                });

                adjust_step_height()


                break;

            case 4:

                //switch to lists

                fill_field_selects();

                $('#lists_container').off('change', '*[data-list_col]');

                $('#lists_container').on('change', '*[data-list_col]', function (){

                    var $parent = $(this).parents('td').first();
                    if ($(this).val() != '')
                    {
                        $parent.find('*[data-list_col]').prop('disabled', true);
                        $(this).prop('disabled', false);
                        if ($(this).data('rel') == 'list_relation')
                        {
                            var $rel_attr = $parent.find('*[data-rel=list_relation_attr]');
                            $rel_attr.prop('disabled', false);
                            $.get('/admin/crud_setup/rel_attr/'+$('input[data-model_name]').val()+'/'+$(this).val(), function(res) {

                                    var fields = res;

                                    //var options = "<option value=''>Choose pivot " + $(this).data('rel') + " key</option>";
                                    //for (var i in fields) {
                                    //    if (fields[i] != 'id') {
                                    //        options += "<option value='" + fields[i] + "'>" + fields[i] + "</option>";
                                    //    }
                                    //}
                                }
                            , 'json');
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
                    }
                });

                break;


        }

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
                fields.push($(this).data('rel'));
            }
        );

        if (fields.length) {
            $('select[data-rel="field_select"]').each(function () {
                $(this).empty();
                var html = '<option value="">Choose  field</option>';
                for (var i = 0; i < fields.length; i++) {
                    html += '<option value="' + fields[i] + '">' + fields[i] + '</option>';
                }
                $(this).html(html);
            });
        }
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
                form.submit();
            }
        }).validate({
            errorPlacement: function (error, element)
            {
                element.before(error);
            },
            rules: {

            }
        });

    }



})($, CRUD, window);