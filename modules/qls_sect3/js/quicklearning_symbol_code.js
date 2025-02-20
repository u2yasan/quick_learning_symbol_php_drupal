(function ($, Drupal, once) {
  Drupal.behaviors.codeAccordion = {
    attach: function (context, settings) {
      once("codeAccordion", ".toggle-code", context).forEach((element) => {
        $(element).on("click", function () {
          var button = $(this);
          var codeBlock = button.next("pre").find(".code-block");

          codeBlock.slideToggle(200, function () {
            button.text(codeBlock.is(":visible") ? "Hide Code" : "Show Code");
          });

        });
      });
    }
  };
})(jQuery, Drupal, once);