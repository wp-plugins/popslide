function popslide_save_form(css_editor) {

	var $ = jQuery.noConflict();

	tinyMCE.triggerSave();
	css_editor.save();

	$('#popslide-spinner').show();

	var $form = $('#popslide-form');

	var data = {
		'action': 'popslide_ajax_save_form',
		'nonce': $form.find('input[name=nonce]').val(),
		'data': $form.find('input:not([type=submit]):not([name=nonce]):not([name=_wp_http_referer]), select, textarea').serialize()
	};

    $.post(ajaxurl, data, function(response) {

    	$('#popslide-spinner').hide();

    	/*if (preview == true)
    		$('.popslide-nav .popslide-preview').submit();*/

	});

}


jQuery(document).ready(function($) {

	var css_editor = CodeMirror.fromTextArea(document.getElementById('popslide-custom-css'), {
		lineNumbers: true,
	    mode: 'css',
	    indentWithTabs: true,
	    indentUnit: 4
	});

	$('.popslide-colorpicker').wpColorPicker();

	$('#popslide-form').submit(function(event) {

		event.preventDefault();
		popslide_save_form(css_editor);

	});

	/*$('.popslide-nav .popslide-preview').click(function(event) {

		event.preventDefault();
		popslide_save_form(true);

	});*/

	$('.popslide-nav .nav-tab:not(.popslide-preview)').click(function(event) {

		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');

		$('.popslide-tab').hide();
		$($(this).attr('href')).show();

		css_editor.refresh();

	});

	$('.popslide-tab input[type="checkbox"]').change(function(event) {

		$( '.' + $(this).attr('id') + '_more' ).slideToggle();

		css_editor.refresh();

	});

	$('.popslide-image-radio img').click(function(event) {

		var $parent = $(this).parent();

		$parent.find('img').removeClass('checked');
		$parent.find('input').val( $(this).data('value') );

		$(this).addClass('checked');

	});

});