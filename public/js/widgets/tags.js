;(function ($, crud){
    $.widget("crud.crud_tags", {
        options: {},
        _create: function(){
            var hidden = this.element.parent().find("input[type=hidden]");
            var form = this.element.parents("form:first");
            var list = new Bloodhound({
                datumTokenizer: function (datum) {
                    return Bloodhound.tokenizers.whitespace(datum.name);
                },
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                limit: 5,
                remote: {
                    url: crud.format_setting("model_autocomplete_url", {model: this.element.data('model'), scope: form.data('crud_scope')}),
                    replace: function(url, query) {
                        return url + "?q=" + query;
                    }
                }
            });

            list.initialize();
            var tagApi = this.element.tagsManager({
                hiddenTagListId: hidden.attr('id'),
                deleteTagsOnBackspace: false,
                prefilled: hidden.val()
            });
            this.element.typeahead(null, {
                minLength: 1, // send AJAX request only after user type in at least X characters
                source: list.ttAdapter()
            }).on('typeahead:selected', function (e, d) {
                tagApi.tagsManager("pushTag", d);
            });

        }
    });
})(jQuery, CRUD)