;(function ($, crud, win){
    $.widget("crud.crud_select", {
        options: {},
        _create: function(){
            
            this.element.selectpicker({
                liveSearch: true

            });

        },
        refresh: function()
        {
            this.element.selectpicker('refresh');
        }
    });
})(jQuery, CRUD, window)