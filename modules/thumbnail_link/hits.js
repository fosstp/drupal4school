(function($){

  var thumbnail_hit = function() {
    var args = {};
    args.nid = $(this).attr('id');
    var rsp = $.ajax({
      data    : args,
      url     : Drupal.settings.basePath + "thumbnail_link/handle",
      type    : "get",
      datatype: "html",
      async   : false
    }).responseText;
    return true;
  }

  Drupal.behaviors.thumbnail_link = {
    attach: function(context, settings) {
      $('.thumbnail_link', context).click(thumbnail_hit);
    }
  };

})(jQuery);