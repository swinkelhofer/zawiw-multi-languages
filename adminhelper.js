jQuery(document).ready(function() {
	if(jQuery('#zawiw-multi-lang-isfallback').prop('checked'))
		jQuery('#select_main').css('display','none');
	jQuery('#zawiw-multi-lang-isfallback').change(function() {
		jQuery('#select_main').toggle();
	});
});

