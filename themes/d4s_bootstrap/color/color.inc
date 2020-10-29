<?php

/**
 * @file
 * Lists available colors and color schemes for the Bootstrap Barrio theme.
 */
$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'top' => '站台橫幅背景',
    'bottom' => '導覽列背景',
    'bg' => '主內容背景',
    'sub' => '次內容背景',
    'sidebar' => '側欄背景',
    'sidebarborders' => '側欄邊框',
    'footer' => '頁尾背景',
    'modal' => '版權宣告背景',
    'titleslogan' => '網站名稱及標語',
    'text' => '文字顏色',
    'link' => '超連結顏色',
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Blue Lagoon (default)'),
      'colors' => [
        'top' => '#055a8e',
        'bottom' => '#1d84c3',
        'bg' => '#ffffff',
        'sub' => '#888888',
        'sidebar' => '#f6f6f2',
        'sidebarborders' => '#f9f9f9',
        'footer' => '#024268',
        'modal' => '#292929',
        'titleslogan' => '#fffeff',
        'text' => '#3b3b3b',
        'link' => '#0071b3',
      ],
    ],
    'firehouse' => [
      'title' => t('Firehouse'),
      'colors' => [
        'top' => '#cd2d2d',
        'bottom' => '#d64e4e',
        'bg' => '#ffffff',
        'sub' => '#f9d2d2',
        'sidebar' => '#f1f4f0',
        'sidebarborders' => '#ededed',
        'footer' => '#7b3d1e',
        'modal' => '#1f1d1c',
        'titleslogan' => '#fffeff',
        'text' => '#888888',
        'link' => '#d6121f',
      ],
    ],
    'ice' => [
      'title' => t('Ice'),
      'colors' => [
        'top' => '#d0d0d0',
        'bottom' => '#c2c4c5',
        'bg' => '#ffffff',
        'sub' => '#ffffff',
        'sidebar' => '#ffffff',
        'sidebarborders' => '#cccccc',
        'footer' => '#0cb4da',
        'modal' => '#016b83',
        'titleslogan' => '#000000',
        'text' => '#4a4a4a',
        'link' => '#019dbf',
      ],
    ],
    'plum' => [
      'title' => t('Plum'),
      'colors' => [
        'top' => '#4c1c58',
        'bottom' => '#593662',
        'bg' => '#fffdf7',
        'sub' => '#f2cc5a',
        'sidebar' => '#edede7',
        'sidebarborders' => '#e7e7e7',
        'footer' => '#6a3ca5',
        'modal' => '#2c2c28',
        'titleslogan' => '#ffffff',
        'text' => '#301313',
        'link' => '#9d408d',
      ],
    ],
    'slate' => [
      'title' => t('Slate'),
      'colors' => [
        'top' => '#4a4a4a',
        'bottom' => '#4e4e4e',
        'bg' => '#ffffff',
        'sub' => '#ffffff',
        'sidebar' => '#ffffff',
        'sidebarborders' => '#d0d0d0',
        'footer' => '#454545',
        'modal' => '#161617',
        'titleslogan' => '#ffffff',
        'text' => '#3b3b3b',
        'link' => '#0073b6',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'assets/css/colors.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],

  // Preview files.
  'preview_library' => 'd4s_bootstrap/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'd4s_bootstrap'),
      ],
    ],
  ],
];
