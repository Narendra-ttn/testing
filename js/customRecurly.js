// jQuery to make customizations to recurly to payment form
Drupal.behaviors.recurlyFormAlter = {
  attach: function (context, settings) {
    $('#recurlyjs-update-billing, #recurlyjs-subscribe').find('label:not(label[for=edit-vat-number],label[for=edit-coupon-code])').each(
      function (index, object) {
        $(object).prop('class', 'form-required');
      });

    //open the colorbox when user click on the change plan button
    $('.change-plan-pop-up', context).click(function () {
      var plan_code = $(this).attr('data-attr');
      var html = $('#change_plan_message_' + plan_code).html();
      $("#overlay").hide();
      $.colorbox({height: "200px", width: "200px", html: html});
      $("#overlay").hide();
    });
  }
}