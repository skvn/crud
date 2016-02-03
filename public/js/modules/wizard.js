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


                } else {
                    alert('List alias "'+alias+'" already in use. Use another alias');
                    return false;
                }
            },

            wizard_add_relation: function (elem) {
                var type = $('#rtype').val();
                if (type == '')
                {
                    alert('Choose relation type');
                    return;
                }

                $('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment"  data-skip_arr="1" data-fragment="tpl_rel_'+type+'" data-container="r_container"></a>').appendTo($('body')).click().remove();

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

                //$('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment" data-skip_arr="1" data-fragment="tpl_f_'+type+'" data-container="f_container_'+elem.data('rel')+'"></a>').appendTo($('body')).click().remove();

            },

            wizard_add_filter_field: function (elem) {
                var type = elem.val();
                var list = elem.data('list');
                if (type == '')
                {

                    $('#'+list+'_f_cancel_'+elem.data('rel')).find('a').click();
                    return;
                }
                var f_html  = $('#tpl_filter_'+type).html();
                f_html = f_html.replace(new RegExp("_ALIAS_","g"),list);
                f_html = f_html.replace(new RegExp("_F_","g"),elem.data('rel'));

                $('#'+list+'_f_container_'+elem.data('rel')).html(f_html);
                $('#'+list+'_f_cancel_'+elem.data('rel')).show();

            },

            wizard_remove_field: function (elem)
            {
                $('select.ftype[data-rel='+elem.data('field')+']').val('');
                $('#f_container_'+elem.data('field')).html('');
                $('#f_cancel_'+elem.data('field')).hide();

            },

            wizard_remove_filter_field: function (elem)
            {
                var list = elem.data('list');
                $('select.ftype[data-rel='+elem.data('field')+'][data-list='+list+']').val('');
                $('#'+list+'_f_container_'+elem.data('field')).html('');
                $('#'+list+'_f_cancel_'+elem.data('field')).hide();

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
            },
            wizard_remove_relation: function (elem) {

                elem.parents('div[data-relation]').first().remove();
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
                        $('select[data-rel="list_relation"]').each(function () {
                            $(this).empty();
                            var html = '<option value="">Choose relation</option>';
                            for (var i = 0; i < list_rels.length; i++) {
                                html += '<option value="' + list_rels[i] + '">' + list_rels[i] + '</option>';
                            }
                            $(this).html(html);
                        });
                    }

                });


                break;

            case 2:
                //switch to fields
                //exclude  local keys
                $('#f_container').find('tr').show();

                $('#r_container').find('select[data-attr=local_key]').each(function (){
                    $('#f_container').find('tr[data-rel='+$(this).val()+']').hide();
                });
                break;

            case 4:

                //switch to lists

                $('#lists_container').off('change', '*[data-list_col]');

                $('#lists_container').on('change', '*[data-list_col]', function (){

                    var $parent = $(this).parents('td').first();
                    if ($(this).val() != '')
                    {
                        $parent.find('*[data-list_col]').prop('disabled', true);
                        $(this).prop('disabled', false);
                    } else {
                        $parent.find('*[data-list_col]').prop('disabled', false);
                    }

                });

                break;


        }

    }
    function init_steps()
    {

        $("#form").steps({
            bodyTag: "fieldset",
            transitionEffect: "slideLeft",
            autoFocus: true,
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