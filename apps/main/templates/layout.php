<!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]> <html class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html class="no-js ie8"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <?php include_http_metas() ?>
  <?php include_metas() ?>
  <meta name="language" content="<?php echo $sf_user->getCulture() ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <?php less_include_stylesheets() ?>
  <script type="text/javascript" src="/js/libs/modernizr-1.6.min.js"></script>
  <?php less_include_javascripts() ?>
  <?php include_title() ?>
</head>
<body lang="<?php echo $sf_user->getCulture() ?>">
  <?php echo $sf_content ?>
  
  <!--[if lt IE 7 ]>
    <script src="/js/libs/dd_belatedpng.js"></script>
    <script>DD_belatedPNG.fix('img, .png_bg');</script>
  <![endif]-->
</body>
</html>
