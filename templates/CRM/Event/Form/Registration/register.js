cj(function($){


  $('input[type=radio]').on('change', function() {
    if($(this).attr('price')){
      eval( 'var option = ' + $(this).attr('price') );
      var optionPart = option[1].split(optionSep);
      var participants = optionPart[1] - 1;
      $('#additional_participants').val(participants);
      }
  });

  var discountcode = $('#discountcode').val();
  $('input[type=radio]').each(function(){
    var el  = $(this);
    if($(el).attr('name') != 'payment_processor'){
      if(discountcode.length > 0){ //check if discount code is empty;
         var text = $('label[for='+ el.attr('id') +']').text();
        if(text.indexOf('Additional participant') !== -1){
          el.parent().parent().hide();
        }
      }else{
        if(el.data('amount') == 0){
          el.parent().parent().hide();
        }
      }
      if(el.attr('value') == 0){
        el.prop('checked', true);
        el.parent().parent().hide();
      }
    }
  });

});

