/**
 * @file
 * Define horizontal tabs functionality.
 */

/**
 * Triggers when form values inside a horizontal tab changes.
 *
 * This is used to update the summary in horizontal tabs in order to know what
 * are the important fields' values.
 *
 * @event summaryUpdated
 */

(function ($, Drupal, drupalSettings) {
  var handleFragmentLinkClickOrHashChange = function handleFragmentLinkClickOrHashChange(e, $target) {
    $target.parents('.horizontal-tabs__pane').each(function (index, pane) {
      $(pane).data('horizontalTab').focus();
    });
  };

  Drupal.behaviors.horizontalTabs = {
    attach: function (context) {
      var width = drupalSettings.widthBreakpoint || 640;
      var mq = '(max-width: ' + width + 'px)';

      if (window.matchMedia(mq).matches) {
        return;
      }

      $('body').once('horizontal-tabs-fragments').on('formFragmentLinkClickOrHashChange.horizontalTabs', handleFragmentLinkClickOrHashChange);

      $(context).find('[data-horizontal-tabs-panes]').once('horizontal-tabs').each(function () {
        var $this = $(this).addClass('horizontal-tabs__panes');
        var focusID = $this.find(':hidden.horizontal-tabs__active-tab').val();
        var tabFocus = void 0;

        // Check if there are some details that can be converted to
        // horizontal-tabs.
        var $details = $this.find('> details');
        if ($details.length === 0) {
          return;
        }

        // Create the tab column.
        var tab_list = $('<ul class="horizontal-tabs__menu"></ul>');
        $this.wrap('<div class="horizontal-tabs clearfix"></div>').before(tab_list);

        // Transform each details into a tab.
        $details.each(function () {
          var $that = $(this);
          var horizontal_tab = new Drupal.horizontalTab({
            title: $that.find('> summary').text(),
            details: $that
          });
          tab_list.append(horizontal_tab.item);
          $that
            .removeClass('collapsed')
            // prop() can't be used on browsers not supporting details element,
            // the style won't apply to them if prop() is used.
            .attr('open', true)
            .addClass('horizontal-tabs__pane')
            .data('horizontalTab', horizontalTab);
          if (this.id === focusID) {
            tabFocus = $that;
          }
        });

        $(tab_list).find('> li').eq(0).addClass('first');
        $(tab_list).find('> li').eq(-1).addClass('last');

        if (!tabFocus) {
          // If the current URL has a fragment and one of the tabs contains an
          // element that matches the URL fragment, activate that tab.
          var $locationHash = $this.find(window.location.hash);
          if (window.location.hash && $locationHash.length) {
            tabFocus = $locationHash.closest('.horizontal-tabs__pane');
          }
          else {
            tabFocus = $this.find('> .horizontal-tabs__pane').eq(0);
          }
        }
        if (tabFocus.length) {
          tabFocus.data('horizontalTab').focus();
        }
      });
    }
  };

  Drupal.horizontalTab = function (settings) {
    var self = this;
    $.extend(this, settings, Drupal.theme('horizontalTab', settings));

    this.link.attr('href', '#' + settings.details.attr('id'));

    this.link.on('click', function (e) {
      e.preventDefault();
      self.focus();
    });

    this.link.on('keydown', function (event) {
      if (event.keyCode === 13) {
        event.preventDefault();
        self.focus();
        $('.horizontal-tabs__pane :input:visible:enabled').eq(0).trigger('focus');
      }
    });

    this.details
      .on('summaryUpdated', function () {
        self.updateSummary();
      })
      .trigger('summaryUpdated');
  };

  Drupal.horizontalTab.prototype = {
    focus: function focus() {
      this.details
        .siblings('.horizontal-tabs__pane')
        .each(function () {
          var tab = $(this).data('horizontalTab');
          tab.details.hide();
          tab.item.removeClass('is-selected');
        })
        .end()
        .show()
        .siblings(':hidden.horizontal-tabs__active-tab')
        .val(this.details.attr('id'));
      this.item.addClass('is-selected');

      $('#active-horizontal-tab').remove();
      this.link.append('<span id="active-horizontal-tab" class="visually-hidden">' + Drupal.t('(active tab)') + '</span>');
    },

    updateSummary: function () {
      this.summary.html(this.details.drupalGetSummary());
    },

    tabShow: function tabShow() {
      this.item.show();

      this.item.closest('.js-form-type-horizontal-tabs').show();

      this.item.parent().children('.horizontal-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');

      this.details.removeClass('horizontal-tab--hidden').show();

      this.focus();
      return this;
    },

    tabHide: function tabHide() {
      this.item.hide();

      this.item.parent().children('.horizontal-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');

      this.details.addClass('horizontal-tab--hidden').hide();

      var $firstTab = this.details.siblings('.horizontal-tabs__pane:not(.horizontal-tab--hidden)').eq(0);
      if ($firstTab.length) {
        $firstTab.data('horizontalTab').focus();
      }
      else {
        this.item.closest('.js-form-type-horizontal-tabs').hide();
      }
      return this;
    }
  };

  Drupal.theme.horizontalTab = function (settings) {
    var tab = {};
    tab.item = $('<li class="horizontal-tabs__menu-item" tabindex="-1"></li>')
      .append(tab.link = $('<a href="#"></a>')
        .append(tab.title = $('<strong class="horizontal-tabs__menu-item-title"></strong>').text(settings.title))
          .append(tab.summary = $('<span class="horizontal-tabs__menu-item-summary"></span>')
        )
      );
    return tab;
  };
})(jQuery, Drupal, drupalSettings);
