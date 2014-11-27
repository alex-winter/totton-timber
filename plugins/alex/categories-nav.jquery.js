
jQuery( function($) {

	function load_content(link) {
		$("#t3-content > *").fadeOut(300);
		$("#t3-content").load( link + " #t3-content > *", function(){
			$("#t3-content > *").fadeIn(400);
			//_gaq.push(['_trackPageview', link]);
		});
	}

	$(".category-list-item").click( function(e){
		e.preventDefault();
		
		var me = $(this);
		var link = me.attr("href");
		var ul = me.parent().find(".dropdown-menu");
		var parent = me.parent("li");
		var menu_items = $(".category_nav li");

		menu_items.each( function(){
			var item = $(this);
			item.removeClass('current active');
			item.find(".dropdown-menu").hide(500);	
		});

		if ( me.parent().parent().hasClass('dropdown-menu')) {
			alert("this is a sub item");
		} else {
			alert("this is a root item");
			ul.toggle(500);
		}
		
		parent.addClass('current active');
		load_content(link);
	});

});