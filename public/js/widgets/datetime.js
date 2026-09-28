;(function ($, crud){
    $.widget("crud.crud_datetime", {
        options: {},
        _create: function(){
            switch (this.element.data('type'))
            {
                case 'date':
                    createDates(this.element);
                break;
                case 'date_time':
                    createDateTime(this.element);
                break;
                default:
                    alert('Unknown type of date control');
                break;
            }
        }
    });

    function createDates(cont)
    {
        if (cont.is('input')) {
            createDate(this.element);
        }
        $('input', cont).each(function () {
            createDate($(this));
        });
    }

    function createDate(elem)
    {
        elem.datepicker(
            {
                todayBtn: "linked",
                keyboardNavigation: true,
                forceParse: true,
                calendarWeeks: true,
                autoclose: true,
                weekStart:1,
                language: 'ru'
            }).on('changeDate', function(e) {
            // Revalidate the date field
            //$(this).parents('form:first').bootstrapValidator('revalidateField',$(this).find('input'));
        });
    }

    function createDateTime(elem)
    {
        elem.val(moment(elem.val));
        elem.datetimepicker(
            {
                locale: "ru",
                format: elem.data('format')

            }).on('dp.change', function(e) {
            // Revalidate the date field
            //$(this).parents('form:first').bootstrapValidator('revalidateField',$(this).find('input'));
        });
    }
})(jQuery, CRUD)