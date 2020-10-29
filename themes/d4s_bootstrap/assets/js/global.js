(function ($, Drupal) {
  'use strict';
  // //////////////////////////////////////////////////////////////////////////////
  // [ DROPDOWN LANGUAGE MENU MOBILE ]
  $(() => {
    if (window.matchMedia('(max-width: 768px)').matches) {
      $('#language-selector').addClass('dropup');
    }
    else {
      $('#language-selector').removeClass('dropup');
    }
  });

  // //////////////////////////////////////////////////////////////////////////////
  // [ DESKTOP MENU - SEARCH TOGGLE ]
  $('#menu-main').on('click', '.form-actions', function (e) {
    $('#menu-main')
      .toggleClass('show-search')
      .find('.form-search')
      .focus();
    $(this).toggleClass('active');
    e.preventDefault();
  });

  // //////////////////////////////////////////////////////////////////////////////
  // [ ANCHOR OFFSET ]
  $(() => {
    const target = window.location.hash;
    // only try to scroll to offset if target has been set in location hash

    if (target !== '') {
      const $target = jQuery(target);
      jQuery('html, body')
        .stop()
        .animate(
        {
          scrollTop: $target.offset().top - 150
        }, // set offset value here i.e. 50
          100,
          'swing',
          () => {
            window.location.hash = target - 80;
          }
        );
    }
  });

  // //////////////////////////////////////////////////////////////////////////////
  // [ MOBILE NAV - SMOOTH OPENING/CLOSING SUBMENU ]
  if (window.matchMedia('(max-width: 768px)').matches) {
    let open = $('#menu-main .dropdown-toggle');
    let a = $('#menu-main .nav').find('.dropdown-toggle');

    open.click(function (e) {
      e.preventDefault();
      let $this = $(this);
      let speed = 250;
      if ($this.hasClass('active-menu') === true) {
        $this
          .removeClass('active-menu')
          .next('.dropdown-menu')
          .slideUp(speed);
      }
      else if (a.hasClass('active-menu') === false) {
        $this
          .addClass('active-menu')
          .next('.dropdown-menu')
          .slideDown(speed);
      }
      else {
        a.removeClass('active-menu')
          .next('.dropdown-menu')
          .slideUp(speed);
        $this
          .addClass('active-menu')
          .next('.dropdown-menu')
          .delay(speed)
          .slideDown(speed);
      }
    });
  }
  // //////////////////////////////////////////////////////////////////////////////
  // [ BOOTSTRAP COMPONENTS ]

  // [ TOOLTIP ]
  $(() => {
    $('[data-toggle="tooltip"]').tooltip({
      delay: {
        show: 100,
        hide: 250
      }
    });
  });

  // [ POPOVERS ]
  $(() => {
    $('[data-toggle="popover"]').popover();
  });

  // [ CAROUSEL ]
  $(() => {
    $('.carousel').carousel({
      interval: 2000
    });
  });

  // [ MODAL ]
  $(() => {
    $('#bs-modal').on('shown.bs.modal', () => {
      $('#bs-modal-button').trigger('focus');
    });
  });
  // //////////////////////////////////////////////////////////////////////////////
  // [ SUBMIT WITH ENTER KEYSTROKE ]
  $(() => {
    $('.form-search').keydown(function (event) {
      // enter has keyCode = 13, change it if you want to use another button
      if (event.keyCode === 13) {
        this.form.submit();
        return false;
      }
    });
  });

  // //////////////////////////////////////////////////////////////////////////////
  // [ HIDE / SHOW IMAGE LOGO ON SCROOL ]
  $(() => {
    const logo = $('#header-menu .navbar-brand');
    $(window).scroll(() => {
      const scroll = $(window).scrollTop();

      if (scroll >= 85) {
        if (!logo.hasClass('show-logo')) {
          logo.removeClass('hide-logo').addClass('show-logo');
        }
      }
      else if (!logo.hasClass('hide-logo')) {
        logo.removeClass('show-logo').addClass('hide-logo');
      }
    });
  });
})(jQuery, Drupal);
