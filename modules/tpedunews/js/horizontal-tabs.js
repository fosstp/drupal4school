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

  'use strict';

  /**
   * This script transforms a set of details into a stack of horizontal tabs.
   *
   * Each tab may have a summary which can be updated by another
   * script. For that to work, each details element has an associated
   * 'horizontalTabCallback' (with jQuery.data() attached to the details),
   * which is called every time the user performs an update to a form
   * element inside the tab pane.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behaviors for horizontal tabs.
   */
  Drupal.behaviors.horizontalTabs = {
    attach: function (context) {
      var width = drupalSettings.widthBreakpoint || 640;
      var mq = '(max-width: ' + width + 'px)';

      if (window.matchMedia(mq).matches) {
        return;
      }

      $(context).find('[data-horizontal-tabs-panes]').once('horizontal-tabs').each(function () {
        var $this = $(this).addClass('horizontal-tabs__panes');
        var focusID = $this.find(':hidden.horizontal-tabs__active-tab').val();
        var tab_focus;

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
            .data('horizontalTab', horizontal_tab);
          if (this.id === focusID) {
            tab_focus = $that;
          }
        });

        $(tab_list).find('> li').eq(0).addClass('first');
        $(tab_list).find('> li').eq(-1).addClass('last');

        if (!tab_focus) {
          // If the current URL has a fragment and one of the tabs contains an
          // element that matches the URL fragment, activate that tab.
          var $locationHash = $this.find(window.location.hash);
          if (window.location.hash && $locationHash.length) {
            tab_focus = $locationHash.closest('.horizontal-tabs__pane');
          }
          else {
            tab_focus = $this.find('> .horizontal-tabs__pane').eq(0);
          }
        }
        if (tab_focus.length) {
          tab_focus.data('horizontalTab').focus();
        }
      });
    }
  };

  /**
   * The horizontal tab object represents a single tab within a tab group.
   *
   * @constructor
   *
   * @param {object} settings
   *   Settings object.
   * @param {string} settings.title
   *   The name of the tab.
   * @param {jQuery} settings.details
   *   The jQuery object of the details element that is the tab pane.
   *
   * @fires event:summaryUpdated
   *
   * @listens event:summaryUpdated
   */
  Drupal.horizontalTab = function (settings) {
    var self = this;
    $.extend(this, settings, Drupal.theme('horizontalTab', settings));

    this.link.attr('href', '#' + settings.details.attr('id'));

    this.link.on('click', function (e) {
      e.preventDefault();
      self.focus();
    });

    // Keyboard events added:
    // Pressing the Enter key will open the tab pane.
    this.link.on('keydown', function (event) {
      if (event.keyCode === 13) {
        event.preventDefault();
        self.focus();
        // Set focus on the first input field of the visible details/tab pane.
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

    /**
     * Displays the tab's content pane.
     */
    focus: function () {
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
      // Mark the active tab for screen readers.
      $('#active-horizontal-tab').remove();
      this.link.append('<span id="active-horizontal-tab" class="visually-hidden">' + Drupal.t('(active tab)') + '</span>');
    },

    /**
     * Updates the tab's summary.
     */
    updateSummary: function () {
      this.summary.html(this.details.drupalGetSummary());
    },

    /**
     * Shows a horizontal tab pane.
     *
     * @return {Drupal.horizontalTab}
     *   The horizontalTab instance.
     */
    tabShow: function () {
      // Display the tab.
      this.item.show();
      // Show the horizontal tabs.
      this.item.closest('.js-form-type-horizontal-tabs').show();
      // Update .first marker for items. We need recurse from parent to retain
      // the actual DOM element order as jQuery implements sortOrder, but not
      // as public method.
      this.item.parent().children('.horizontal-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');
      // Display the details element.
      this.details.removeClass('horizontal-tab--hidden').show();
      // Focus this tab.
      this.focus();
      return this;
    },

    /**
     * Hides a horizontal tab pane.
     *
     * @return {Drupal.horizontalTab}
     *   The horizontalTab instance.
     */
    tabHide: function () {
      // Hide this tab.
      this.item.hide();
      // Update .first marker for items. We need recurse from parent to retain
      // the actual DOM element order as jQuery implements sortOrder, but not
      // as public method.
      this.item.parent().children('.horizontal-tabs__menu-item').removeClass('first')
        .filter(':visible').eq(0).addClass('first');
      // Hide the details element.
      this.details.addClass('horizontal-tab--hidden').hide();
      // Focus the first visible tab (if there is one).
      var $firstTab = this.details.siblings('.horizontal-tabs__pane:not(.horizontal-tab--hidden)').eq(0);
      if ($firstTab.length) {
        $firstTab.data('horizontalTab').focus();
      }
      // Hide the horizontal tabs (if no tabs remain).
      else {
        this.item.closest('.js-form-type-horizontal-tabs').hide();
      }
      return this;
    }
  };

  /**
   * Theme function for a horizontal tab.
   *
   * @param {object} settings
   *   An object with the following keys:
   * @param {string} settings.title
   *   The name of the tab.
   *
   * @return {object}
   *   This function has to return an object with at least these keys:
   *   - item: The root tab jQuery element
   *   - link: The anchor tag that acts as the clickable area of the tab
   *       (jQuery version)
   *   - summary: The jQuery element that contains the tab summary
   */
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
