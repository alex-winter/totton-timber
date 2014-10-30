jQuery(document).ready( function() {
	
	function load_content (link) {
		jQuery( "#t3-content" ).fadeOut(300).load( link + " #t3-content > *", function(){
			jQuery( "#t3-content" ).fadeIn(400);
		});
	}

	jQuery(".category_nav .nav a").click( function(e){
		e.preventDefault();
	});

	jQuery(".category_nav .nav li").click( function(){
		var this_ul = jQuery(this).find("ul");
		var this_link = jQuery(this).find("a").attr("href");

		if( this_ul.hide() ) {
			this_ul.show(500);
			load_content (this_link);
			this_ul.find("ul").hide();
		} else {
			this_ul.hide(500);
		}
	});

	jQuery(".category_nav .nav > li").click( function(e){
		e.preventDefault();
		jQuery(".category_nav .nav > li ul").not($(this).find("ul")).hide(500);
	});
});