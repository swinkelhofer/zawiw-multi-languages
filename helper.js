jQuery(document).ready(function() {
	//jQuery('.menu-primary').append('<li class="language entry-title menu-item"><a href="">DE</a><ul class="menu"><li class="menu-item"><a href="">DE</a></li><li class="menu-item"><a href="">EN</a></li></ul></li>');
	jQuery.get("../../wp-content/plugins/zawiw-multi-languages/ajax.php", function(data) {
		jQuery('.menu-primary').append(data);

	});
});
