;(function ($, crud, win){
    $.widget("crud.crud_select", {
        options: {},
        _create: function(){
            
            if (this.element.data('remote') && this.element.data('model')) {

                this.element.select2({
                    ajax: {
                        url: crud.format_setting("model_select_options_url", {model: this.element.data('model')}),
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term, // search term
                                page: params.page
                            };
                        },
                        cache: true
                    },
                    language: win.CURRENT_LOCALE,
                    minimumInputLength: 3,
                });

            } else {
                this.element.selectpicker({
                    liveSearch: true

                });
            }
        }
    });
})(jQuery, CRUD, window)