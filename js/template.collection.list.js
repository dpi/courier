(function ($) {

  "use strict";

  Drupal.behaviors.courier = {
    attach: function (context, settings) {
      $('.template_collection[template_collection]').once('courier_template').each(function () {
        var $tc = $(this);

        $(this).append('<div class="editor_container ui attached segment " />');
        $(this).append('<div class="properties_container ui bottom attached segment " />');
        $tc.find('.editor_container').hide();
        $tc.find('.properties_container').hide();

        $tc.find('ul.templates a').each(function() {
          var template_entity_type = $(this).attr('entity_type');
          $tc.find('.editor_container').append('<div class="editor ui raised segment ' + template_entity_type + '" />');
          $tc.find('.editor_container').find('.editor').hide();
          var url = 'courier/collection/' + $tc.attr('template_collection') + '/template/' + template_entity_type;
          Drupal.ajax({
            url: Drupal.url(url),
            event: 'click',
            progress: {type: 'fullscreen'},
            element: this
          });
          Drupal.ajax({
            url: Drupal.url('courier/collection/' + $tc.attr('template_collection') + '/tokens'),
            event: 'click',
            progress: {type: 'fullscreen'},
            element: this
          });
          $(this).on('click', function() {
            $(this).closest('ul').find('a').removeClass('active');
            $(this).addClass('active');
          });
        });
      });
    }
  };

  Drupal.AjaxCommands.prototype.courierTemplate = function (ajax, response, status) {
    var tcid = response.template_collection;
    var channel = response.channel;
    var $tc = $('.template_collection[template_collection=' + tcid + ']');

    if (response.operation == 'open') {
      $tc.find('.editor').hide();
      $tc.find('.editor.' + channel).show();
      $tc.find('.editor_container').show();
      $tc.find('.properties_container').show();
      var $a = $tc.find('ul.templates a');
      $a.addClass('blue');
    }
    if (response.operation == 'close') {
      $tc.find('.editor_container').hide();
      $tc.find('.properties_container').hide();
      $tc.find('ul.templates a').removeClass('active');
      $tc.find('.editor.' + channel).empty();
    }

  };

})(jQuery);
