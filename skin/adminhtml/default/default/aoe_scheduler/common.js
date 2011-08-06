$.noConflict();
jQuery(function() {
	jQuery('.timeline-box').scrollLeft(jQuery('.timeline-panel').width());

	jQuery('.task').tooltip({
		appendToBody: true,
		predelay: 100,
		position: 'bottom center',
		onShow: function() { this.getTrigger().addClass('active'); },
		onHide: function() { this.getTrigger().removeClass('active'); }
	}).dynamic();

})
