
function load_content(link) {
	jQuery("#t3-content > *").fadeOut(150);
	jQuery("#t3-content").load( link + " #t3-content > *", function(){
		jQuery("#t3-content > *").fadeIn(150);
		_gaq.push(['_trackPageview', link]);
	});
}

jQuery(document).on('click', '.hikashop_container', function(e){
	e.preventDefault();
	//alert('clicked');
	var me = jQuery(this);
	var link = me.find("a").attr("href");
	load_content(link);
});

jQuery( function($) {
	$("ul.category_nav li").click( function(e){
		e.preventDefault();
		var me = jQuery(this);
		//alert(me.attr('class'));
		var link = me.find('a').attr("href");
		//alert(link);
		var ul = me.find(".dropdown-menu");
		var menu_items = jQuery('[class*="item-"]');
		menu_items.each( function(){
			var item = jQuery(this);
			item.removeClass('current active');
			//item.find(".dropdown-menu").hide(500);	
		});
		if ( me.parent().hasClass('dropdown-menu')) {
			//alert("this is a sub item");
			
		} else {
			//alert("this is a root item");
			ul.toggle(500);
		}	
		me.addClass('current active');
		load_content(link);
	});
});


