;(function($, crud, win){
    var i18n = win.i18n;
    bind_events();
    $.widget("crud.crud_form", {
        options: {},
        _create: function()
        {
            console.log('form created');
            
            var $form = this.element;
            var self = this;

            //init controls
            crud.trigger('form.init', {form: $form});

            $form.find('.nav-tabs li').on('click', function () {
                crud.trigger('form.tab_click', {form: $form, tab: $(this)});
            });

            $("[required]", $form).each(function(){
                var validator = $(this).data('crud-validator');
                if (validator) {
                    var v = validator.split(",");
                    if ($.inArray('required', v) < 0) {
                        v.push('required');
                    }
                    validator = v.join(",");
                } else {
                    validator = "required";
                }
                $(this).removeAttr('required').attr('data-crud-validator',validator).data('crud-validator', validator);
            });

            //submit
            $('input[type=submit],button[type=submit]', $form).on('click', function () {
                var frm = $(this).parents("form:first");
                prepare_form(frm, $(this));
            });

            $form.on('submit', function(e){
                e.preventDefault();
                crud.trigger('form.before_validate', {form: $form});
                if (!validate_form($form)) {
                    return;
                }
                crud.trigger('form.before_submit', {form: $form});
                $form.ajaxSubmit({
                    type: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    context: crud.doc.body,
                    success: function(res){
                        crud.trigger('form.after_submit', {form: $form});
                        if (res.success) {
                            if ($form.data('crud_model')) {
                                var ref_scope = $form.data('crud_model')+'_'+$form.data('crud_scope');
                                var ref_model = $form.data('crud_model');
                                if ($form.data('close')) {
                                    crud.trigger("crud.reload", res);
                                    crud.trigger('crud.cancel_edit', {rel:$form.data('rel')});

                                } else {
                                    crud.trigger("crud.reload", res);
                                    var ref = $form.data('crud_model') + '_' + $form.data('crud_scope');
                                    var table = $('*[data-list_table_ref='+ref+']');
                                    if (table.data('form_type') == 'tabs') {
                                        crud.trigger('crud.cancel_edit', {rel:$form.data('rel')});
                                    }
                                    crud.trigger('crud.edit_element', { id: res.crud_id, ref: ref_scope, model: ref_model});

                                }
                                //$form.trigger('reset');
                                //crud.reset_selects();
                            } else {
                                if (res.message) {
                                    alert(res.message);
                                }
                                if ($form.data('callback_event')) {
                                    crud.trigger($form.data('callback_event'));
                                }
                                crud.trigger('crud.submitted', {form_id: $form.attr('id'), res: res, frm: $form});
                            }
                            if ($form.data("close")) {
                                $form.parents(".modal:first").modal('hide');
                            }
                            if ($form.data("reload")) {
                                crud.loc.reload();
                            }
                        } else {
                            if (typeof res.errors != "undefined") {
                                for (var f in res.errors) {
                                    self.showError(f, res.errors[f].join('<br />'));
                                }
                                self.gotoError();
                            } else {
                                alert(crud.format_error(res.error));
                            }
                        }
                    },
                    error: function(res){
                        //crud.trigger('form.error_submit', {form: $form});
                        //crud.toggle_form_progress($form);
                        if (res.responseJSON && res.responseJSON.error && res.responseJSON.error.message) {
                            alert(res.responseJSON.error.message)
                        } else {
                            alert(i18n.say('error_sending_request'));
                        }
                    }
                });
            });

            $('input[type=file]', $form).on('change', function (e) {

                var spl = $(this).val().split("\\");
                var name = spl[(spl.length-1)];
                var expl_name = name.split(".");
                name = expl_name[0];
                $("input[data-title_for='"+$(this).attr('name')+"']", $form).val(name);
            });

            $($form).on('change', 'select,input', function(e){
                crud.trigger('form.change', {form: $form, elem: $(this)});
            });


        },
        showFields: function(names)
        {
            for (var i in names) {
                //alert(names[i]+ ':' +$(".form-group[data-ref="+names[i]+"]", this.element).length);
                var c = $(".row[data-ref="+names[i]+"]", this.element);
                c.show();
                //$("[data-crud-validator]", c).each(function(){
                //    var v = $(this).data('crud-validator');
                //    if ((""+v).substr(0, 1) == "~")
                //    {
                //        v = (""+v).substr(1);
                //    }
                //    $(this).attr('data-crud-validator', v).data('crud-validator', v);
                //});
                $("[data-crud-validator]", c).data('crud-validator-disabled', '0').attr('data-crud-validator-disabled', '0');
                //var e = $("[data-crud-validator]", c);
                //e.data('crud-validator-disabled', e.data('crud-validator')).attr('data-crud-validator', 'required');
            }
        },
        hideFields: function(names)
        {
            for (var i in names) {
                var c = $(".row[data-ref="+names[i]+"]", this.element);
                c.hide();
                //$("[data-crud-validator]", c).each(function(){
                //    var v = $(this).data('crud-validator');
                //    if ((""+v).substr(0, 1) != "~")
                //    {
                //        v = "~" + (""+v);
                //    }
                //    $(this).attr('data-crud-validator', v).data('crud-validator', v);
                //});
                //$("[data-crud-validator=required]", $(".row[data-ref="+names[i]+"]", this.element)).data('crud-validator', 'required-disabled').attr('data-crud-validator', 'required-disabled');
                $("[data-crud-validator]", c).data('crud-validator-disabled', '1').attr('data-crud-validator-disabled', '1');
            }
        },
        resetErrors: function()
        {
            $(".has-error", this.element).removeClass("has-error").find('*[data-rel=error]').hide();
            $("[data-remote-validator]", this.element).removeAttr("data-remote-validator");
        },
        showError: function(control, message)
        {
            var p = this.element;
            if (control.indexOf('.') > 0) {
                var segments = control.split('.');
                $('[data-context-limiter]', this.element).each(function(){
                    var c = $(this);
                    if (c.data('model') === segments[0] && parseInt(c.data('id')) === parseInt(segments[1])) {
                        p = c;
                        control = segments[2];
                    }
                });
            }
            var row = $(".form-group[data-ref="+control+"]", p);
            //$(".form-group:first", row).addClass("has-error").find('*[data-rel=error]').html(message).show();
            row.addClass("has-error").find('*[data-rel=error]').html(message).show();
        },
        gotoError: function()
        {
            var e = $('.has-error:first', this.element);
            if (e.length < 1)
            {
                return;
            }
            if ($("[data-toggle=tab]", this.element).length > 0) {
                var tab = e.parents(".tab-pane:first").attr('id');
                if (tab) {
                    $(".nav-tabs", this.element).find("a[href='#"+tab+"']").click();
                }
            }
            $('html, body').animate({
                scrollTop: e.offset().top
            }, 500);
        }
    });

    function bind_events()
    {
        var crud_actions = {
            open_form: function(elem)
            {
                crud.init_modal(elem.data("model"), elem.data("id"));
            },

            clone_fragment: function (elem)
            {

                var tpl_id = elem.data('fragment');
                var container_id = elem.data('container');
                var skip_arr = elem.data('skip_arr');
                var only_children = elem.data('only_children');

                if (only_children)
                {
                    var $tpl = $($('#'+tpl_id, crud.getActiveTab(elem)).html());
                } else {
                    var $tpl = $('#'+tpl_id, crud.getActiveTab(elem)).clone(true).attr('id','');
                }

                //console.log($tpl);

                var qtyAdded = $('#'+container_id, crud.getActiveTab(elem)).find('*[data-added]').length;
                $tpl =  $($('<div>').append($tpl).html().replace(new RegExp("(\\[NUM\\])", 'g'),qtyAdded+1));



                $tpl.find('*[name]').each(function ()
                {
                    $(this).attr('disabled', false);
                    var name = $(this).attr('name');
                    if (!skip_arr) {
                        if (name.indexOf('[]') > 0) {
                            var newName = name.replace('[]', '[-' + (qtyAdded + 1) + ']');
                            $(this).attr('name', newName)
                        }
                    }
                });
                $("*[data-delayed-widget]", $tpl).each(function(){
                    var meth = $(this).data('delayed-widget');
                    $(this)[meth]();
                });
                $tpl.attr('data-added',1);
                //calc order
                var ord  = $('#'+container_id, crud.getActiveTab(elem)).find('*[data-order]:visible').length;
                $tpl.find('*[data-order]').val((ord+1));

                $tpl.appendTo($('#'+container_id, crud.getActiveTab(elem))).show();

            }
        };

        crud.add_actions(crud_actions);
        crud.bind('crud.cancel_edit', function(data){

            //?? tab ??
            var id = 'tab_'+data.rel;
            if ($('div#'+id+'.tab-pane').length) {
                var cont = $('div#'+id);
                cont.parents('div[data-tabs_container]').first().find('.nav-tabs li:first a:first').click();
                var id = cont.attr('id');
                cont.remove();
                $('a[href=#'+id+']').parent().remove();

                $("html, body").animate({ scrollTop: 0 }, "slow");

            } else {
                //alert('hide');
                $('form[data-rel='+data.rel+']').parents(".modal:first").modal('hide');
                //alert('after hide');
                //$('.modal-backdrop').remove();
            }

        });



        crud.bind('crud.edit_element', function(data){

            if (data.ref) {
                data.table = $('*[data-list_table_ref='+data.ref+']');
            }

            var model = data.table.data('crud_table')?data.table.data('crud_table'):data.table.data('crud_tree');
            if (!model && data.model) {
                model = data.model;
            }
            if (data.table && data.table.data('form_type') == 'tabs') {
                //open edit  tab
                crud.init_edit_tab(model, data.id, {table: data.table, scope: data.table.data('crud_scope'), rargs:data.rargs?data.rargs:{}});
            } else {
                //init edit modal
                crud.init_modal(model, data.id, {scope: data.table.data('crud_scope'), rargs:data.rargs?data.rargs:{}});
            }
        });

        $(crud.doc).on('click', '.crud_submit', function (e) {
            e.preventDefault();
            var frm = $(this).parents("form:first");
            prepare_form(frm, $(this));
            frm.submit();

        });

        crud.bind("form.before_submit", function(data) {
            toggle_progress(data['form']);
            init_progress(data['form']);
        });
        crud.bind("form.after_submit", function(data){
            toggle_progress(data['form']);
        });
        crud.bind("form.error_submit", function(data){
            toggle_progress(data['form']);
        });
        crud.bind("form.init", function(data){
            $("*[data-widget]", data['form']).each(function () {

                var wname = $(this).data('widget');
                $(this)[wname]();
            });
        });
    }



    function prepare_form(frm, elem)
    {
        var attrs = ['close', 'reload'];
        for (var i =0; i<attrs.length; i++) {
            if (elem.data(attrs[i]) != undefined) {
                frm.data(attrs[i], elem.data(attrs[i]));
            }
        }

    }

    function validate_form(frm)
    {
        console.log("VALIDATING");
        var valid = true;
        var remote = [];
        $(".has-error", frm).removeClass("has-error").find('*[data-rel=error]').hide();
        $("[data-remote-validator]", frm).removeAttr("data-remote-validator");
        return true;
        $('[data-crud-validator]', frm).each(function() {
            var e = $(this);
            if (e.is(":disabled"))
            {
                return;
            }
            if (e.data('crud-validator-disabled') == "1")
            {
                console.log(e.attr('name') + ' validation disabled');
                return;
            }
            var validators = e.data('crud-validator').split(",");
            for (var i in validators)
            {
                //alert(validators[i]);
                switch (validators[i])
                {
                    case 'required':
                        if (!e.val())
                        {
                            valid = false;
                            e.parents(".form-group:first").addClass("has-error").find('*[data-rel=error]').html('Это поле необходимо заполнить').show();
                            console.log("Error: " + e.attr('name'));
                        }
                    break;
                    case 'slug':
                        var row = {validator: 'slug', value: e.val()};
                        row = crud.addModelParams(row, e);
                        e.attr("data-remote-validator", remote.length);
                        remote.push(row);
                    break;
                }
            }
        });
        if (remote.length) {
            $.ajaxSetup({async: false});
            var o = $.post(crud.format_setting('model_validate_url', {}), {validates: remote}, function(res) {
                $.ajaxSetup({async: true});
                for (var i in res) {
                    if (!res[i].valid) {
                        var elm = $('[data-remote-validator='+i+']', frm);
                        elm.parents(".form-group:first").addClass("has-error").find('*[data-rel=error]').html(res[i].error_message).show();
                        console.log("Error: " + elm.attr('name'));
                        valid = false;
                    }
                }
            });
        }
        console.log(valid ? "SUCCESS" : "FAILED");
        return valid;
    }

    function toggle_progress(elem)
    {
        $('.modal-footer button, .modal-footer .progress', elem).toggleClass('hide');
        $('.modal-footer button', elem).each(function () {

            if (!$(this).hasClass('hide')) {
                $(this).removeAttr('disabled');
            }
        });

    }

    function init_progress(elem)
    {
        var bar = $('.modal-footer .progress .progress-bar', elem);
        bar.parent().removeClass('hide');
        var current_perc = 0;
        bar.css('width', (current_perc)+'%');
        var perc = 99;
        var progress = setInterval(function() {
            if (current_perc>=perc) {
                clearInterval(progress);
            } else {
                current_perc +=1;
                bar.css('width', (current_perc)+'%');
            }

        }, 20);
    }





})(jQuery, CRUD, window)