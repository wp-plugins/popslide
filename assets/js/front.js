function popslide() {

	var $ = jQuery.noConflict();

	var $pop = $('#popslide');

	if (popslide_settings.position == 'top') $pop.slideDown(popslide_settings.animation_duration, 'linear');
	else if (popslide_settings.position == 'bottom') $pop.slideDown(popslide_settings.animation_duration, 'linear');

	$pop.find('.popslide-close span').click(function() {

		$pop.slideUp(popslide_settings.animation_duration, 'linear');

		var data = {
			'action': 'popslide_ajax_save_cookie'
		};

	    $.post(popslide_settings.ajaxurl, data, function(response) {

		});

	});

}

jQuery(document).ready(function($) {

	// move div if position is on top
	if (popslide_settings.position == 'top')
		$('#popslide').detach().prependTo('body');

	window.setTimeout(function() { popslide(); }, popslide_settings.timeout);

});