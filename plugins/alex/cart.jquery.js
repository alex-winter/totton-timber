$.noConflict();
jQuery( function($) {

	//Element Funcitons
	var cart_table = 'form[name="hikashop_cart_form"]';
	function elem_empty_cart(){
		$(".hikashop_cart_module").html('<div id="hikashop_cart" class="hikashop_cart empty_cart">The&nbsp;cart&nbsp;is&nbsp;empty</div>');
		$(".hikashop_cart_module").css({"border-radius":"0 0 10px 10px"});
	}

	//Data Funcitons
	var cart_qty = 0;
	var product_total = 0;
	function data_cart_qty(){
		cart_qty = 0;
		$(cart_table+' .hikashop_product_quantity_field').each(function(){
			cart_qty = cart_qty + parseInt($(this).val());
		});
		return cart_qty;
	}
	function data_cart_product_total(){
		product_total = 0;
		$(cart_table+' table tbody > tr').each(function(){
			product_total++;
		});
		return product_total;
	}

	$(".delete-product-from-cart").each( function(){
		var jscode = $(this).attr('onclick');
		$(this).removeAttr('onclick');
		$(this).attr('data-onclick', jscode);
	});

	$(".toggle-cart.closed").click( function(){
		$(".hikashop_cart form").stop().slideDown(300, 'swing');
		$(".hikashop_cart_module").css({"border-radius":"0"});
		$(this).removeClass("closed").addClass("open");
		$(this).removeClass("btn-success").addClass("btn-danger");
		$(this).find("span").html("&nbsp;Hide&nbsp;Cart");		
	});

	$(".toggle-cart.open").click( function(){
		$(".hikashop_cart form").stop().slideUp(300, 'swing', function(){
			$(".hikashop_cart_module").css({"border-radius":"0 0 10px 10px"});
		});
		$(this).removeClass("open").addClass("closed");
		$(this).removeClass("btn-danger").addClass("btn-success");
		$(this).find("span").html("&nbsp;View&nbsp;Cart");
	});

	$(".delete-product-from-cart").click( function(e){
		e.preventDefault();
		
		var me = $(this);
		var tr = me.closest("tr");
		var href = me.attr('href');
		var jscode = me.attr('data-onclick');

		tr.animate({
			"background-color": "#ff0000",
			"height":"0", 
			"opacity":"0"
		}, 500, function(){
			this.remove();

			$.ajax({
				url: href,
				success: function(){
					eval(jscode);
				}
			});

			$(".cart-total-qty span").text(data_cart_qty());
			
			if (data_cart_product_total() == 0)
				elem_empty_cart();	
		});
	});
});
