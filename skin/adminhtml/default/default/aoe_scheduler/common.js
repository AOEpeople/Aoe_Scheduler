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
		fill: '#DDDDDD',
		shrinkToFit: true,
		padding: 0,
		killTitle: false,
		cornerRadius: 0,
		spikeLength: 15,
		spikeGirth: 5,
		strokeWidth: 0,
		clickAnywhereToClose: true,
		positions: ['top', 'bottom', 'left', 'right'],
		closeWhenOthersOpen: true
	});
})