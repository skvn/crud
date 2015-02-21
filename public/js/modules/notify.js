;(function($, CRUD){

    $(function(){
        window.setInterval(tick, 30000);
        tick();
    });

    function tick()
    {
        $.get('/util/notify/fetch', {}, function(notify){
            if (notify['id'])
            {
                $('#popup_dialog_content').html(notify['message']);
                $('#popup_dialog_title').html(notify['title']);
                $('#popup_dialog').modal('show');
            }
        });
    }

})($, CRUD);