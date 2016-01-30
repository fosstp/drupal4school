<!-- #header -->
<div id="header">
	<!-- #header-inside -->
    <div id="header-inside" class="container_12 clearfix">
    	<!-- #header-inside-left -->
        <div id="header-inside-left" <?php if ($page['search_area']) :?>class="grid_9"<?php else :?>class="grid_12"<?php endif; ?>>
            
			<?php if ($logo): ?>
            <a href="<?php print check_url($front_page); ?>" title="<?php print t('Home'); ?>"><img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" /></a>
            <?php endif; ?>
     
            <?php if ($site_name || $site_slogan): ?>
            <div class="clearfix">
            <?php if ($site_name): ?>
            <span id="site-name"><a href="<?php print check_url($front_page); ?>" title="<?php print t('Home'); ?>"><?php print $site_name; ?></a></span>
            <?php endif; ?>
            <?php if ($site_slogan): ?>
            <span id="slogan"><?php print $site_slogan; ?></span>
            <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div><!-- EOF: #header-inside-left -->
        
        <!-- #header-inside-right -->
		<?php if ($page['search_area']) :?>
        <div id="header-inside-right" class="grid_3">

			<?php if ($page['search_area']) :?>
			<?php $access_key=theme_get_setting('access_second_menu','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key): ?><a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="搜尋區">:::</a><?php endif; ?>
			<?php print render($page['search_area']); ?>
		    <?php endif; ?>

        </div><!-- EOF: #header-inside-right -->
		<?php endif; ?>
    </div><!-- EOF: #header-inside -->

</div><!-- EOF: #header -->

<!-- #header-menu -->
<div id="header-menu">
	<!-- #header-menu-inside -->
    <div id="header-menu-inside" class="container_12 clearfix">
    
    	<div class="grid_12">
            <div id="navigation" class="clearfix">
            <?php
			global $user;
            $menu_id = '';
			if ($logged_in) {
               $account = user_load($user->uid);
               if (module_exists('simsauth') && simsauth_get_authname($user->uid)) {
                 if ($account->userclass == 'student') {
                   $menu_id = theme_get_setting('student_menu','Accessibility');
                 }
                 else {
                   $menu_id = theme_get_setting('teacher_menu','Accessibility');
                 }
               }
               else {
                 $menu_id = variable_get('menu_main_links_source', 'main-menu');
               }
            }
            else {
              $menu_id = theme_get_setting('anonymous_menu','Accessibility');
            }
            if (module_exists('i18n_menu')) {
              $main_menu_tree = i18n_menu_translated_tree($menu_id);
            } else {
              $main_menu_tree = menu_tree($menu_id); 
            }
            $access_key=theme_get_setting('access_second_menu','Accessibility');
            if (theme_get_setting('access_brick','Accessibility') && $access_key) {
               array_unshift($main_menu_tree, array( '#theme' => 'menu_link__main_menu', '#attributes' => array( 'class' => array('first','leaf','brick') ),'#title' => ':::', '#href' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '#localized_options' => array( 'attributes' => array( 'title' => '導覽列', 'accesskey' => $access_key ) ), '#below' => array() ) );
			}
            print drupal_render($main_menu_tree);
            ?>
            </div>
        </div>
        
    </div><!-- EOF: #header-menu-inside -->

</div><!-- EOF: #header-menu -->

<!-- #banner -->
<div id="banner">

	<?php print render($page['banner']); ?>

	<?php
		$page_list=theme_get_setting('slideshow_pages','Accessibility');
		$pages=array_filter(explode(',', $page_list));
	?>

    <?php if (theme_get_setting('slideshow_display','Accessibility') && count($pages)>0): ?>
    
    <?php if ($is_front): ?>
    
    <!-- #slideshow -->
    <div id="slideshow">

		<?php foreach ($pages as $nid): ?>

		<!--slider-item-->
        <div class="slider-item">
            <div class="content container_12">
            	<div class="grid_12">
                <!--slider-item content-->
                <?php print drupal_render(node_view(node_load($nid))) ?>
                <!--EOF:slider-item content-->
                
                </div>
            </div>
        </div>
        <!--EOF:slider-item-->

		<?php endforeach; ?>

	</div>
    <!-- EOF: #slideshow -->
    
    <!-- #slider-controls-wrapper -->
    <div id="slider-controls-wrapper">
        <div id="slider-controls" class="container_12">
            <ul id="slider-navigation">
				<?php foreach ($pages as $nid): ?>
				<li><a href="#"></a></li>
		        <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <!-- EOF: #slider-controls-wrapper -->
    
    <?php endif; ?>
    
	<?php endif; ?>  

</div><!-- EOF: #banner -->


<!-- #content -->
<div id="content">
	<!-- #content-inside -->
    <div id="content-inside" class="container_12 clearfix">
    
        <?php if ($page['sidebar_left']) :?>
        <!-- #sidebar-left -->
        <div id="sidebar-left" class="grid_2">
            <?php $access_key=theme_get_setting('access_sidebar_left','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?><a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="左側邊欄">:::</a><?php endif; ?>
            <?php print render($page['sidebar_left']); ?>
        </div><!-- EOF: #sidebar-left -->
        <?php endif; ?>
        
        <?php if ($page['sidebar_left'] && $page['sidebar_right']) { ?>
        <div class="grid_8">
        <?php } elseif ($page['sidebar_left'] || $page['sidebar_right']) { ?>
        <div id="main" class="grid_10">
		<?php } else { ?>
        <div id="main" class="grid_12">    
        <?php } ?>
            
            <?php if (theme_get_setting('breadcrumb_display','Accessibility')): ?>
			<?php print $breadcrumb; endif; ?>

			<?php if ($page['highlighted']): ?><div id="highlighted"><?php print render($page['highlighted']); ?></div><?php endif; ?>
       
            <?php if ($messages): ?>
            <div id="console" class="clearfix">
            <?php print $messages; ?>
            </div>
            <?php endif; ?>
     
            <?php if ($page['help']): ?>
            <div id="help">
            <?php print render($page['help']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($action_links): ?>
            <ul class="action-links">
            <?php print render($action_links); ?>
            </ul>
            <?php endif; ?>
            
		    <?php print render($title_prefix); ?>
			<?php $access_key=theme_get_setting('access_content','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
            <a accesskey="<?php print theme_get_setting('access_content','Accessibility'); ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="主要內容區">:::</a>
			<?php if ($title): ?>
            <h1>
		    <?php endif; ?>
			<?php print $title ?>
			</h1>
            <?php endif; ?>
            <?php print render($title_suffix); ?>
            
            <?php if ($tabs): ?><?php print render($tabs); ?><?php endif; ?>
            <?php print render($page['content']); ?>
            
            <!-- ?php print $feed_icons; ? -->
            
        </div><!-- EOF: #main -->
        
        <?php if ($page['sidebar_right']) :?>
        <!-- #sidebar-right -->
        <div id="sidebar-right" class="grid_2">
            <?php $access_key=theme_get_setting('access_sidebar_right','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
            <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="右側邊欄">:::</a>
		    <?php endif; ?>
        	<?php print render($page['sidebar_right']); ?>
        </div><!-- EOF: #sidebar-right -->
        <?php endif; ?>  

    </div><!-- EOF: #content-inside -->

</div><!-- EOF: #content -->

<!-- #footer -->    
<div id="footer">
	<!-- #footer-inside -->
    <div id="footer-inside" class="container_12 clearfix">
    
        <div class="footer-area grid_4">
        <?php if ($page['footer_first']) :?>
        <?php $access_key=theme_get_setting('access_footer_first','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key): ?>
        <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="頁尾上層一區">:::</a>
	    <?php endif; ?>
        <?php print render($page['footer_first']); ?>
	    <?php endif; ?>
        </div><!-- EOF: .footer-area -->
        
        <div class="footer-area grid_4">
        <?php if ($page['footer_first']) :?>
        <?php $access_key=theme_get_setting('access_footer_second','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
        <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="頁尾上層二區">:::</a>
	    <?php endif; ?>
        <?php print render($page['footer_second']); ?>
	    <?php endif; ?>
        </div><!-- EOF: .footer-area -->
        
        <div class="footer-area grid_3">
        <?php if ($page['footer_first']) :?>
        <?php $access_key=theme_get_setting('access_footer_third','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
        <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="頁尾上層三區">:::</a>
	    <?php endif; ?>
        <?php print render($page['footer_third']); ?>
	    <?php endif; ?>
        </div><!-- EOF: .footer-area -->
       
    </div><!-- EOF: #footer-inside -->

</div><!-- EOF: #footer -->

<!-- #footer-bottom -->    
<div id="footer-bottom">

	<!-- #footer-bottom-inside --> 
    <div id="footer-bottom-inside" class="container_12 clearfix">
    	<!-- #footer-bottom-left --> 
    	<div id="footer-bottom-left" class="grid_8">
        
			<?php if (count($secondary_menu)>0) :?>
			<?php
				$access_key=theme_get_setting('access_second_menu','Accessibility');
                if (theme_get_setting('access_brick','Accessibility') && $access_key) {
				$tab_index=1;
				foreach ($secondary_menu as $id => $element) {
					$secondary_menu[$id]['attributes']['tabindex']=$tab_index;
					$tab_index++;
					$title=$secondary_menu[$id]['title'];
					if (!isset($secondary_menu[$id]['attributes'])) $secondary_menu[$id]['attributes']=array( 'title' => $title ); 
					if (!isset($secondary_menu[$id]['attributes']['title'])) $secondary_menu[$id]['attributes']['title']=$title;
					if (!$secondary_menu[$id]['attributes']['title']) $secondary_menu[$id]['attributes']['title']=$title;
					$secondary_menu[$tab_index.' leaf'] = $secondary_menu[$id];
					unset($secondary_menu[$id]);
				}
				array_unshift($secondary_menu, array( 'attributes' => array( 'class' => array('brick'), 'title' => '第二層選單列', 'accesskey' => $access_key ), 'href' => 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI'], 'title' => ':::') );
		    } ?>
			<?php print theme('links__system_secondary_menu', array('links' => $secondary_menu, 'attributes' => array('class' => array('secondary-menu', 'links', 'clearfix')))); ?>
		    <?php endif; ?>

	        <?php if ($page['footer']) :?>
			<?php $access_key=theme_get_setting('access_footer','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
		    <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="頁尾下層左側邊欄">:::</a>
		    <?php endif; ?>
            <?php print render($page['footer']); ?>
		    <?php endif; ?>
            
        </div>
    	<!-- #footer-bottom-right --> 
        <div id="footer-bottom-right" class="grid_4">
        
	        <?php if ($page['footer_bottom_right']) :?>
			<?php theme_get_setting('access_footer_bottom','Accessibility'); if (theme_get_setting('access_brick','Accessibility') && $access_key) : ?>
		    <a accesskey="<?php print $access_key; ?>" href="<?php print 'http://' .$_SERVER['HTTP_HOST'] .$_SERVER['REQUEST_URI']; ?>" class="brick" title="頁尾下層右側邊欄">:::</a>
		    <?php endif; ?>
        	<?php print render($page['footer_bottom_right']); ?>
		    <?php endif; ?>
        
        </div><!-- EOF: #footer-bottom-right -->
       
    </div><!-- EOF: #footer-bottom-inside -->
    
    <?php if (theme_get_setting('credits_display','Accessibility')): ?>
    <!-- #credits -->   
    <div id="credits" class="container_12 clearfix">
        <div class="grid_12">
        <p>針對台灣無障礙標準而設計的版型，設計者：國語實小 <a href="mailto:leejoneshane@gmail.com">李忠憲</a></p>
        </div>
    </div>
    <!-- EOF: #credits -->
    <?php endif; ?>

</div><!-- EOF: #footer -->