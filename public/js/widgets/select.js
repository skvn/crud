;(function ($, crud, win){
    $.widget("crud.crud_select", {
        options: {},
        _create: function(){
            
            this.element.selectpicker({
                liveSearch: true

            });

        }
    });
})(jQuery, CRUD, window)