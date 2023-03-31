jQuery(document).ready(function(){
  jQuery('.payment_method_zalopay_atmcard .bank-group input').iCheck({
    checkboxClass: 'icheckbox_flat-blue',
    radioClass: 'iradio_flat-blue'
  });


  jQuery(".payment_method_zalopay_atmcard .bank-group .bank-item").click(function(){
      jQuery(".bank-item").removeClass("selected");
      jQuery(this).addClass("selected");
			console.log('ok');
  });
});
