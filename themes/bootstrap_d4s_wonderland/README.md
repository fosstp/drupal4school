# d4s_bootstrap theme requirement and installation guide

## Modules and dependency modules
### [Nivo slider](https://www.drupal.org/project/nivo_slider)
- Install [Libraries API](https://www.drupal.org/project/libraries)
- Install [jQuery Update](https://www.drupal.org/project/jquery_update) - configured to use jQuery 1.9
- Copy [Nivo Slider jQuery plugin](https://github.com/gilbitron/Nivo-Slider) to sites/all/libraries/nivo-slider
### [Quick Tabs](https://www.drupal.org/project/quicktabs)
### [Bootstrap Quicktabs](https://www.drupal.org/project/bootstrap_quicktabs)
### [Owl Carousel](https://www.drupal.org/search/site/Owl%20Carousel)
- Copy [Owl Carousel library](http://owlgraphic.com/owlcarousel/owl.carousel.zip) to sites/all/libraries/owlcarousel
### [Font Awesome](https://www.drupal.org/project/fontawesome)
- Copy [Font Awesome](https://github.com/FortAwesome/Font-Awesome) to sites/all/libraries/fontawesome
### [UUID](https://ftp.drupal.org/files/projects/uuid-7.x-1.0-beta2.tar.gz)
### [Node Export](https://ftp.drupal.org/files/projects/node_export-7.x-3.1.tar.gz)

## Should be enabled modules
- Nivo Slider
- jQuery Update
- Libraries
- Quicktabs
- Quicktabs Styles
- Bootstrap Quicktabs
- Owl Carousel
- Owl Carousel Fields
- Owl Carousel UI
- Owl Carousel Views
- Views
- Views Bootstrap
- Views UI
- Variables
- Font Awesome
- System
- Chaos tools
- Calendar
- Date
- Date API
- Date Popup
- Date Views
- Universally Unique ID
- UUID Path
- Features
- Node export
- Node Export Features UI
- Node export features
### Configuration settings and import demo content
1. Set the Lumen theme of bootstrap as default.
2. Add four Owl-Carousel groups(footer-gallery, news-with-image, news-with-image-calendar, owl-carousel) and import the code shown as below.
##### footer-gallery
```
 {"items":1,"itemsDesktop":["1199",0],"itemsDesktopSmall":["979",0],"itemsTablet":["768",0],"itemsTabletSmall":["0",0],"itemsMobile":["479",0],"singleItem":false,"itemsScaleUp":false,"slideSpeed":200,"paginationSpeed":800,"rewindSpeed":1000,"autoPlay":"5000","stopOnHover":false,"navigation":false,"navigationText":["prev","next"],"rewindNav":true,"scrollPerPage":false,"pagination":false,"paginationNumbers":false,"responsive":true,"responsiveRefreshRate":200,"baseClass":"owl-carousel","theme":"owl-theme","lazyLoad":false,"lazyFollow":true,"lazyEffect":"fadeIn","autoHeight":false,"jsonPath":false,"jsonSuccess":false,"dragBeforeAnimFinish":true,"mouseDrag":true,"touchDrag":true,"addClassActive":false,"transitionStyle":false}
```
##### news-with-image-calendar
```
 {"items":4,"itemsDesktop":["1199",0],"itemsDesktopSmall":["979",0],"itemsTablet":["768",0],"itemsTabletSmall":["0",0],"itemsMobile":["479",0],"singleItem":false,"itemsScaleUp":false,"slideSpeed":200,"paginationSpeed":800,"rewindSpeed":1000,"autoPlay":"5000","stopOnHover":false,"navigation":false,"navigationText":["prev","next"],"rewindNav":true,"scrollPerPage":false,"pagination":false,"paginationNumbers":false,"responsive":true,"responsiveRefreshRate":200,"baseClass":"owl-carousel","theme":"owl-theme","lazyLoad":false,"lazyFollow":true,"lazyEffect":"fadeIn","autoHeight":false,"jsonPath":false,"jsonSuccess":false,"dragBeforeAnimFinish":true,"mouseDrag":true,"touchDrag":true,"addClassActive":false,"transitionStyle":false}
```
##### news-with-image
```
 {"items":2,"itemsDesktop":["1199",0],"itemsDesktopSmall":["979",0],"itemsTablet":["768",0],"itemsTabletSmall":["0",0],"itemsMobile":["479",0],"singleItem":false,"itemsScaleUp":false,"slideSpeed":200,"paginationSpeed":800,"rewindSpeed":1000,"autoPlay":"5000","stopOnHover":false,"navigation":false,"navigationText":["prev","next"],"rewindNav":true,"scrollPerPage":false,"pagination":false,"paginationNumbers":false,"responsive":true,"responsiveRefreshRate":200,"baseClass":"owl-carousel","theme":"owl-theme","lazyLoad":false,"lazyFollow":true,"lazyEffect":"fadeIn","autoHeight":false,"jsonPath":false,"jsonSuccess":false,"dragBeforeAnimFinish":true,"mouseDrag":true,"touchDrag":true,"addClassActive":false,"transitionStyle":false}
```
##### owl-carousel
```
 {"items":4,"itemsDesktop":["1199",0],"itemsDesktopSmall":["979",0],"itemsTablet":["768",0],"itemsTabletSmall":["0",0],"itemsMobile":["479",0],"singleItem":false,"itemsScaleUp":false,"slideSpeed":200,"paginationSpeed":800,"rewindSpeed":1000,"autoPlay":"5000","stopOnHover":false,"navigation":true,"navigationText":["prev","next"],"rewindNav":true,"scrollPerPage":false,"pagination":true,"paginationNumbers":false,"responsive":true,"responsiveRefreshRate":200,"baseClass":"owl-carousel","theme":"owl-theme","lazyLoad":false,"lazyFollow":true,"lazyEffect":"fadeIn","autoHeight":false,"jsonPath":false,"jsonSuccess":false,"dragBeforeAnimFinish":true,"mouseDrag":true,"touchDrag":true,"addClassActive":false,"transitionStyle":false}
```
3. Add nivo-slider pictures.
4. Install and enable d4s_bootstrap_features.
5. Fix missing image of footer_gallery.
6. Set bootstoap quicktabs as default style to quicktabs.
7. Map blocks to regions(Nivo slider->Top Bar, View: booking_system_views: Block->Top Content, View: owl_carousel->Content, 校園公告->Content, 主要內容->Content, View: owl_carousel_calendar->Content, View: footer_gallery_views: Block->Footer Left, View: footer_other_web_view: Block->Footer Center, View: footer_calendar_views: Block->Footer Right, )

<!-- @file Instructions on how to sub-theme the Drupal Bootstrap base theme using the CDN Starterkit. -->
<!-- @defgroup subtheme_cdn -->
<!-- @ingroup subtheme -->
# CDN Starterkit

The CDN Starterkit is rather simple to set up. You don't have to do anything
until you wish to override the default [Drupal Bootstrap] base theme settings
or provide additional custom CSS.

- [Prerequisite](#prerequisite)
- [Override Styles](#styles)
- [Override Settings](#settings)
- [Override Templates and Theme Functions](#registry)

## Prerequisite
Read the @link subtheme Sub-theming @endlink parent topic.

## Override Styles {#styles}
Open `./subtheme/css/style.css` and modify the file to your liking.

## Override Settings {#settings}
Please refer to the @link subtheme_settings Sub-theme Settings @endlink topic.

## Override Templates and Theme Functions {#registry}
Please refer to the @link registry Theme Registry @endlink topic.

[Drupal Bootstrap]: https://www.drupal.org/project/bootstrap
[Bootstrap Framework]: http://getbootstrap.com
[jsDelivr CDN]: http://www.jsdelivr.com
