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
                if (alias == '')
                {
                    alias = 'default';
                }
                if (!used_list_aliases[alias])
                {
                    used_list_aliases[alias] = 1;
                    var list_html  = $('#list_tpl').html();
                    list_html = list_html.replace(new RegExp("_ALIAS_","g"),alias);
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
                    alert('Choose field type');
                    return;
                }
                var f_html  = $('#tpl_f_'+type).html();
                f_html = f_html.replace(new RegExp("_ALIAS_","g"),elem.data('rel'));
                $('#f_container_'+elem.data('rel')).html(f_html);

                //$('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment" data-skip_arr="1" data-fragment="tpl_f_'+type+'" data-container="f_container_'+elem.data('rel')+'"></a>').appendTo($('body')).click().remove();

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
            }


        };
        crud.add_actions(crud_actions);
    }

    function init_step_events(stepIndex)
    {

        switch (stepIndex){

            case 1:
                //switch to relations
                $('#r_container').off('change', 'input');
                $('#r_container').on('change', 'input', function () {


                    $('#r_container').find('*[data-relation]').each(function () {

                        relations = [];
                        var rel = {};
                        $(this).find('input[data-attr]').each(function () {
                            rel[$(this).data('attr')] = $(this).val();
                        });
                        relations.push(rel);

                    });

                    console.log(relations);
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
                // Always allow going backward even if the current step contains invalid fields!
                //if (currentIndex > newIndex)
                //{
                //    return true;
                //}


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
                form.validate().settings.ignore = ":disabled";

                // Start validation; Prevent form submission if false
                return form.valid();
            },
            onFinished: function (event, currentIndex)
            {
                var form = $(this);
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