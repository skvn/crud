;(function ($, crud, win){
    $.widget("crud.crud_ent_select", {
        options: {},
        _create: function(){
            
                this.element.select2({
                    ajax: {
                        url: crud.format_setting("model_select_options_url", {model: this.element.data('model')}),
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            var args = {
                                q: params.term, // search term
                                page: params.page,
                            };
                            for (var i in this.data())
                            {
                                if (i.indexOf('url_') === 0)
                                {
                                    args[i.replace('url_', '')] = this.data(i);
                                }
                            }
                            return args;
                        },
                        cache: true
                    },
                    language: win.CURRENT_LOCALE,
                    minimumInputLength: 3,
                });


        }
    });
})(jQuery, CRUD, window)