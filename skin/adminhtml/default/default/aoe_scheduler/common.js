/**
 *
 */
$.noConflict();
jQuery(function() {
	jQuery('.timeline-box').scrollLeft(jQuery('.timeline-panel').width());

	jQuery('.detailwrap').bt({
		trigger: 'click',
		contentSelector: "jQuery(this).find('.details')",
		//offsetParent: self,
		fill: 'white',
		shrinkToFit: true,
		padding: 0,
		killTitle: false,
		cornerRadius: 0,
		spikeLength: 15,
		spikeGirth: 5,
		clickAnywhereToClose: true,
		closeWhenOthersOpen: true
	});
})