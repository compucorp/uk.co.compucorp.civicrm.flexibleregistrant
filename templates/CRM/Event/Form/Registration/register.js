cj(function($){
  $('input[type=radio]').on('change', function() {
    if($(this).attr('price')){
      eval( 'var option = ' + $(this).attr('price') );
      var optionPart = option[1].split(optionSep);
      var participants = optionPart[1] - 1;
      $('#additional_participants').val(participants);
      }
  });

  $('input[type=radio]').each(function(){
    var el  = $(this);
    if(el.data('amount') == 0){
      el.parent().parent().hide();
    }
    if(el.attr('value') == 0){
      el.prop('checked', true);
      el.parent().parent().hide();
    }
  });

});

