$.noConflict();
jQuery(function () {

    jQuery('.task').tooltip({
        offsetParent: 'body',
        predelay: 100,
        position: 'bottom center',
        onShow: function () {
            this.getTrigger().addClass('active');
        },
        onHide: function () {
            this.getTrigger().removeClass('active');
        }
    }).dynamic();

});
