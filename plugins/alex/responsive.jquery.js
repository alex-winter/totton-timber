
function media_querys(ww, wh){
	return
}

jQuery( function(){
	var ww = jQuery(window).width();
	var wh = jQuery(window).height();
	
	jQuery(window).resize( function(){
		media_querys(ww, wh);
	});

	media_querys(ww, wh);

	jQuery("ul.category_nav").clone().appendTo("#off-canvas-nav .t3-mainnav .nav-collapse");
});