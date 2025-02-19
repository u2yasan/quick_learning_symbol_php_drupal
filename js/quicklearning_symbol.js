(function ($, Drupal) {
    Drupal.behaviors.codeAccordion = {
      attach: function (context, settings) {
        $(".toggle-code", context).once("codeAccordion").on("click", function () {
          var button = $(this);
          var codeBlock = button.next(".code-block");
  
          codeBlock.slideToggle(200, function () {
            button.text(codeBlock.is(":visible") ? "Hide Code" : "Show Code");
          });
        });
      }
    };
  })(jQuery, Drupal);