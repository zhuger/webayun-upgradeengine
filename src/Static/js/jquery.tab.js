$.fn.tabs = function(select_hef) {
    var selector = this;

    this.each(function() {
        var obj = $(this);

        $(obj.attr('href')).hide();
        $(obj).click(function() {
            $(selector).removeClass('selected');

            $(selector).each(function(i, element) {
                $($(element).attr('href')).hide();
            });

            $(this).addClass('selected');

            $($(this).attr('href')).show();

            return false;
        });
        if(obj.attr('href')==select_hef){
            obj.click();
        }
    });

    $(this).show();
    if(!select_hef){
        $(this).first().click();
    }

};