;(function ($, crud){
    var mdes = {};
    bind_events();
    $.widget("crud.crud_editor", {
        options: {},
        _create: function(){
            switch (this.element.data('type'))
            {
                case 'tinymce':
                    createTinyMCE(this.element);
                break;
                case 'summernote':
                    createSummernote(this.element);
                break;
                case 'mde':
                    createMDE(this.element);
                break;
                default:
                    createTextarea(this.element);
                break;
            }
        }
    });

    function createMDE(elem)
    {
        mdes[elem.attr('id')] = new SimpleMDE({
            element: elem[0],
            toolbar: ["heading","bold", "italic", "strikethrough", "|", "code","quote","|","unordered-list","ordered-list","|","link","image","table","|","clean-block","|","preview","guide"],
            spellChecker:false
        });
    }

    function createSummernote(elem)
    {
        var toolbar = crud.action(elem, 'summernote_build_toolbar');
        if (!toolbar) {
            toolbar = [
                ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', elem.data('no-media') ? ['link', 'anchor', 'table', 'hr'] : ['picture', 'link', 'anchor', 'video', 'table', 'hr']],
                ['misc', ['codeview', 'typo', 'typo2']]
            ];
        }
        
        elem.summernote({
            //FIXME
            lang: 'ru-RU',
            toolbar: toolbar,
            callbacks: {
                onPaste: function (e) {
                    var ed = $(this);
                    
                    setTimeout(function () {
                        var cleaned = crud.action(ed, 'summernote_paste_html');
                        if (cleaned) {
                            ed.summernote('code', cleaned);
                        }
                    }, 10);
                },
            },
            modules: $.extend($.summernote.options.modules, {anchorDialog: snAnchorDialog()}),
            buttons: {anchor: snAnchorButton(), typo: snTypoButton(), typo2: snTypoButton2()},
            //modules: {
            //AnchorDialog: getAnchorDialog()
            //},
            height: elem.data('height'), linksArray: crud.win.crudAttachOptions
        });
    }

    function createTinyMCE(elem)
    {
        //if (container)
        //{
        //    container.append($('<input type="hidden" name="_attach_to_link_" id="_attach_to_link_" />'))
        //}
        tinymce.init({
            selector: elem.attr('id'),
            height: elem.data('height'),
            toolbar: "paste  | undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code",
            plugins: ["image", "link", "media", "code"] ,
            paste_as_text: true,
            automatic_uploads: false,
            relative_urls : false,
            remove_script_host : false,
            convert_urls : true,
            image_advtab: true,

            file_picker_callback: function(callback, value, meta) {
                if (!$('#tiny_file').length)
                {
                    $('<input type="file" class="input" name="tiny_file" id="tiny_file" style="display: none"/>').appendTo('body');

                    $('#tiny_file').on('change', function (event) {

                        $('.mce-widget.mce-btn.mce-primary.mce-first').css({left:'350px', width:'100px'}).find('button span').text('Loading...');
                        var formData = new FormData();
                        formData.append("file", this.files[0]);
                        $.ajax({
                            url : '/admin/crud/attach_upload',
                            type : 'POST',
                            data : formData,
                            processData: false,
                            contentType: false,
                            success : function(data) {
                                $('.mce-widget.mce-btn.mce-primary.mce-first').css({left:'411px', width:'50px'}).find('button span').text('Ok');

                                $('#_attach_to_link_').val($('#_attach_to_link_').val() + ' '+data.id);
                                if (meta.filetype == 'file') {
                                    callback(data.url);
                                }

                                // Provide image and alt text for the image dialog
                                if (meta.filetype == 'image') {
                                    callback(data.url, {width:data.width, height:data.height});
                                }
                            }
                        });
                    });

                }
                $('#tiny_file').trigger('click');




            },
            //FIXME
            language: i18n.say('locale_short')
        });

    }
    
    function createTextarea(elem) 
    {
        elem.css('height', elem.data('height'));
    }

    function snAnchorDialog()
    {
        var AnchorDialog = function (context) {
            var self = this;
            var ui = $.summernote.ui;

            var $editor = context.layoutInfo.editor;
            var options = context.options;
            var lang = options.langInfo;

            this.initialize = function () {
                var $container = options.dialogsInBody ? $(document.body) : $editor;

                var body = '<div class="form-group">' +
                    '<label>Имя якоря</label>' +
                    '<input class="note-anchor-name form-control" type="text" />' +
                    '</div>';
                var footer = '<button href="#" class="btn btn-primary note-anchor-btn disabled" disabled>Вставить</button>';

                this.$dialog = ui.dialog({
                    className: 'anchor-dialog',
                    title: 'Вставить якорь',
                    fade: options.dialogsFade,
                    body: body,
                    footer: footer
                }).render().appendTo($container);
            };

            this.destroy = function () {
                ui.hideDialog(this.$dialog);
                this.$dialog.remove();
            };

            this.showAnchorDialog = function (ancInfo) {
                return $.Deferred(function (deferred) {
                    var $ancName = self.$dialog.find('.note-anchor-name'),
                        $ancBtn = self.$dialog.find('.note-anchor-btn');

                    ui.onDialogShown(self.$dialog, function () {
                        context.triggerEvent('dialog.shown');

                        $ancName.val(ancInfo.name);

                        $ancName.on('input', function () {
                            ui.toggleBtn($ancBtn, $ancName.val());
                            // if linktext was modified by keyup,
                            // stop cloning text from linkUrl
                            ancInfo.name = $ancName.val();
                        });

                        $ancBtn.one('click', function (event) {
                            event.preventDefault();

                            deferred.resolve({
                                range: ancInfo.range,
                                name: $ancName.val()
                            });
                            self.$dialog.modal('hide');
                        });
                    });

                    ui.onDialogHidden(self.$dialog, function () {
                        // detach events
                        $ancName.off('input keypress');
                        $ancBtn.off('click');

                        if (deferred.state() === 'pending') {
                            deferred.reject();
                        }
                    });

                    ui.showDialog(self.$dialog);
                }).promise();
            };

            /**
             * @param {Object} layoutInfo
             */
            this.show = function () {
                //var ancInfo = context.invoke('editor.getLinkInfo');
                var ancInfo = snAnchorInfo(context);

                context.invoke('editor.saveRange');
                this.showAnchorDialog(ancInfo).then(function (ancInfo) {
                    context.invoke('editor.restoreRange');
                    //context.invoke('editor.createAnchor', ancInfo);
                    snCreateAnchor(context, ancInfo);
                }).fail(function () {
                    context.invoke('editor.restoreRange');
                });
            };
            //context.memo('help.anchorDialog.show', options.langInfo.help['anchorDialog.show']);
        };

        return AnchorDialog;
    }

    function snCreateAnchor(context, ancInfo)
    {
        var ancName = ancInfo.name;
        var rng = ancInfo.range || context.invoke('editor.createRange');
        //var isTextChanged = rng.toString() !== linkText;

        //if (options.onCreateLink) {
        //    linkUrl = options.onCreateLink(linkUrl);
        //}

        //console.log(rng);

        var anchors = [rng.insertNode($('<A name="'+ancName+'"></A>')[0])];
        //var anchor = rng.insertNode($('<A name="'+ancName+'"></A>')[0]);
        //if (isTextChanged) {
        //    rng = rng.deleteContents();
        //    var anchor = rng.insertNode($('<A>' + linkText + '</A>')[0]);
        //    anchors.push(anchor);
        //} else {
        //    anchors = style.styleNodes(rng, {
        //        nodeName: 'A',
        //        expandClosestSibling: true,
        //        onlyPartialContains: true
        //    });
        //}

        //$.each(anchors, function (idx, anchor) {
        //    $(anchor).attr('href', linkUrl);
        //    if (isNewWindow) {
        //        $(anchor).attr('target', '_blank');
        //    } else {
        //        $(anchor).removeAttr('target');
        //    }
        //});

        return rng.select();

        //var startRange = range.createFromNodeBefore(list.head(anchors));
        //var startPoint = startRange.getStartPoint();
        //var endRange = range.createFromNodeAfter(list.last(anchors));
        //var endPoint = endRange.getEndPoint();
        //
        //range.create(
        //    startPoint.node,
        //    startPoint.offset,
        //    endPoint.node,
        //    endPoint.offset
        //).select();
    }

    function snAnchorInfo(context)
    {
        var checkAnc = function (node) {return node && node.nodeName.toUpperCase() === 'A'};
        var rng = context.invoke('editor.createRange').expand(checkAnc);

        // Get the first anchor on range(for edit).
        var $anchor = $(rng.nodes(checkAnc)[0]);

        return {
            range: rng,
            name: $anchor.length ? $anchor.attr('name') : ''
        };
    }

    function snAnchorButton()
    {
        var AnchorButton = function (context) {
            var ui = $.summernote.ui;

            // create button
            var button = ui.button({
                contents: '<i class="fa fa-anchor"/>',
                tooltip: 'Якорь',
                click: function () {
                    // invoke insertText method with 'hello' on editor module.
                    context.invoke('anchorDialog.show');
                    //context.createInvokeHandler('anchorDialog.show')
                }
            });

            return button.render();   // return button as jquery object
        }
        return AnchorButton;
    }

    function snTypoButton()
    {
        var TypoButton = function (context) {
            var ui = $.summernote.ui;

            // create button
            var button = ui.button({
                contents: '<i class="fa fa-check"/>',
                tooltip: 'Типограф (typograf.artlebedev.ru)',
                click: function () {
                    if (confirm('Содержимое будет модифицировано. Продолжить ?'))
                    {
                        $.post('/typo/check', {content: context.code()}, function(res){
                            context.code(res);
                        });
                    }
                }
            });

            return button.render();   // return button as jquery object
        }
        return TypoButton;
    }


    function snTypoButton2()
    {
        var TypoButton2 = function (context) {
            var ui = $.summernote.ui;

            // create button
            var button = ui.button({
                contents: '<i class="fa fa-check"/>',
                tooltip: 'Типограф (www.typograf.ru)',
                click: function () {
                    if (confirm('Содержимое будет модифицировано. Продолжить ?'))
                    {
                        $.post('/typo/check2', {content: context.code()}, function(res){
                            context.code(res);
                        });
                    }
                }
            });

            return button.render();   // return button as jquery object
        }
        return TypoButton2;
    }

    function bind_events()
    {
        crud.bind("form.before_validate", function(data){
            $('textarea[data-widget=crud_editor]', data['form']).each(function(){
                switch($(this).data('type'))
                {
                    case 'mde':
                        if (mdes[this.id])
                        {
                            $(this).val(mdes[this.id].value());
                        }
                    break;
                    case 'summernote':
                        var v = crud.action($(this), 'summernote_save_html');
                        if (v === false) {
                            v = $(this).summernote('code');
                        }
                        $(this).val(v);
                    break;
                }
                $(this).trigger('change');
            });
        });
    }

})(jQuery, CRUD)