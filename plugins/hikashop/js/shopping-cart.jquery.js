
//Element Funcitons
var cart_table = 'form[name="hikashop_cart_form"]';

function elem_empty_cart(){
	jQuery(".hikashop_cart_module").html('<div id="hikashop_cart" class="hikashop_cart empty_cart">The&nbsp;cart&nbsp;is&nbsp;empty</div>');
	jQuery(".hikashop_cart_module").css({"border-radius":"0 0 10px 10px"});
}

//Data Funcitons

var cart_qty = 0;
var product_total = 0;

function data_cart_qty(){
	cart_qty = 0;
	jQuery(cart_table+' .hikashop_product_quantity_field').each(function(){
		cart_qty = cart_qty + parseInt(jQuery(this).val());
	});
	return cart_qty;
}

function data_cart_product_total(){
	product_total = 0;
	jQuery(cart_table+' table tbody > tr').each(function(){
		product_total++;
	});
	return product_total;
}

jQuery(document).ready( function(){
	jQuery(".delete-product-from-cart").each( function(){
		var jscode = jQuery(this).attr('onclick');
		jQuery(this).removeAttr('onclick');
		jQuery(this).attr('data-onclick', jscode);
	});
});

jQuery(document).on("click", ".toggle-cart.closed", function(){
	jQuery(".hikashop_cart form").stop().slideDown(300, 'swing');
	jQuery(".hikashop_cart_module").css({"border-radius":"0"});
	jQuery(this).removeClass("closed").addClass("open");
	jQuery(this).removeClass("btn-success").addClass("btn-danger");
	jQuery(this).find("span").html("&nbsp;Hide&nbsp;Cart");
});

jQuery(document).on("click", ".toggle-cart.open", function(){
	jQuery(".hikashop_cart form").stop().slideUp(300, 'swing', function(){
		jQuery(".hikashop_cart_module").css({"border-radius":"0 0 10px 10px"});
	});
	jQuery(this).removeClass("open").addClass("closed");
	jQuery(this).removeClass("btn-danger").addClass("btn-success");
	jQuery(this).find("span").html("&nbsp;View&nbsp;Cart");
});

jQuery(document).on("click", ".delete-product-from-cart", function(e){
	e.preventDefault();
	var me = jQuery(this);
	var tr = me.closest("tr");
	var href = me.attr('href');
	var jscode = me.attr('data-onclick');

	tr.animate({
		"background-color": "#ff0000",
		"height":"0", 
		"opacity":"0"
	}, 500, function(){
		this.remove();

		jQuery.ajax({
			url: href,
			success: function(){
				eval(jscode);
			}
		});

		jQuery(".cart-total-qty span").text(data_cart_qty());
		
		if (data_cart_product_total() == 0){
			elem_empty_cart();
		}		
	});
});