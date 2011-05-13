<?php

function less_get_stylesheets()
{
  $newStylesheets = array();
  $response = sfContext::getInstance()->getResponse();
  foreach ($response->getStylesheets() as $file => $options)
  {
    if (preg_match('/\.less$/', $file))
    {
      $absolute = false;
      if (isset($options['absolute']) && $options['absolute'])
      {
        unset($options['absolute']);
        $absolute = true;
      }
      if (!isset($options['raw_name']))
      {
        $file = stylesheet_path($file, $absolute);
      }
      $path = sfConfig::get('sf_web_dir') . $file;

      $salt = filemtime($path);
      
      $dir = aFiles::getUploadFolder(array('asset-cache'));
      $name = md5($file.$salt) . '.less.css';
      $compiled = "$dir/" . md5($file.$salt) . '.less.css';
      
      // When minify is turned on we already have a policy that you are responsible for
      // hitting it with a 'symfony cc' to clear the asset cache if you make changes; so the
      // only thing we check for is whether the compiled CSS file exists
      
      // When minify is not turned on (usually in dev) we should do everything we can to be as 
      // tolerant as hitting refresh on a page with plain .css files in it would be, so we need to
      // check the modification time of the .less file against the compiled file
      
      if ((!file_exists($compiled)) || ((!sfConfig::get('app_a_minify')) && (filemtime($compiled) < filemtime($path))))
      {
        if (!isset($lessc))
        {
          $lessc = new lessc();
        }
        $lessc->importDir = dirname($path).'/';
        file_put_contents($compiled, $lessc->parse(file_get_contents($path)));
      }
      $newStylesheets[sfConfig::get('app_a_assetCacheUrl', '/uploads/asset-cache') . '/' . $name] = $options;
    }
    else
    {
      $newStylesheets[$file] = $options;
    }
  }
  return _less_get_assets_body('stylesheets', $newStylesheets);
}

function less_get_javascripts()
{
  if (sfConfig::get('app_a_minify', false))
  {
    $response = sfContext::getInstance()->getResponse();
    return _less_get_assets_body('javascripts', $response->getJavascripts());
  }
  else
  {
    return get_javascripts();
  }
}

function _less_get_assets_body($type, $assets)
{
  $gzip = sfConfig::get('app_a_minify_gzip', false);
  sfConfig::set('symfony.asset.' . $type . '_included', true);

  $html = '';

  // We need our own copy of the trivial case here because we rewrote the asset list
  // for stylesheets after LESS compilation, and there is no way to
  // reset the list in the response object
  if (!sfConfig::get('app_a_minify', false))
  {
		// This branch is seen only for CSS, because javascript calls the original Symfony
		// functionality when minify is off
    foreach ($assets as $file => $options)
    {
      $html .= stylesheet_tag($file, $options);
    }
    return $html;
  }
  
  $sets = array();
  foreach ($assets as $file => $options)
  {
		if (preg_match('/^http(s)?:/', $file))
		{
			// Nonlocal URL. Don't get cute with it, otherwise things
			// like Addthis don't work
			if ($type === 'stylesheets')
			{
      	$html .= stylesheet_tag($file, $options);
			}
			else
			{
      	$html .= javascript_include_tag($file, $options);
			}
			continue;
		}
    /*
     *
     * Guts borrowed from stylesheet_tag and javascript_tag. We still do a tag if it's
     * a conditional stylesheet
     *
     */

    $absolute = false;
    if (isset($options['absolute']) && $options['absolute'])
    {
      unset($options['absolute']);
      $absolute = true;
    }

    $condition = null;
    if (isset($options['condition']))
    {
      $condition = $options['condition'];
      unset($options['condition']);
    }

    if (!isset($options['raw_name']))
    {
      if ($type === 'stylesheets')
      {
        $file = stylesheet_path($file, $absolute);
      }
      else
      {
        $file = javascript_path($file, $absolute);
      }
    }
    else
    {
      unset($options['raw_name']);
    }

    if ($type === 'stylesheets')
    {
      $options = array_merge(array('rel' => 'stylesheet', 'type' => 'text/css', 'media' => 'screen', 'href' => $file), $options);
    }
    else
    {
      $options = array_merge(array('type' => 'text/javascript', 'src' => $file), $options);
    }
    
    if (null !== $condition)
    {
      $tag = tag('link', $options);
      $tag = comment_as_conditional($condition, $tag);
      $html .= $tag . "\n";
    }
    else
    {
      unset($options['href'], $options['src']);
      $optionGroupKey = json_encode($options);
      $set[$optionGroupKey][] = $file;
    }
    // echo($file);
    // $html .= "<style>\n";
    // $html .= file_get_contents(sfConfig::get('sf_web_dir') . '/' . $file);
    // $html .= "</style>\n";
  }
  
  // CSS files with the same options grouped together to be loaded together

  foreach ($set as $optionsJson => $files)
  {
    $groupFilename = '';
    $webpath = sfConfig::get('sf_web_dir');
    foreach ($files as $file)
    {
      $groupFilename .= $file.filemtime($webpath.$file);
      // If your CSS files depend on clever aliases that won't work
      // through the filesystem, we can get them by http. We're caching
      // so that's not terrible, but it's usually simpler faster and less
      // buggy to grab the file content.
    }
    // I tried just using $groupFilename as is (after stripping dangerous stuff) 
    // but it's too long for the OS if you include enough to make it unique
    $groupFilename = md5($groupFilename);
    $groupFilename .= (($type === 'stylesheets') ? '.css' : '.js');
    if ($gzip)
    {
      $groupFilename .= 'gz';
    }
    $dir = aFiles::getUploadFolder(array('asset-cache'));
    if (!file_exists($dir . '/' . $groupFilename))
    {
      $content  = '';
      foreach ($files as $file)
      {
        $path = null;
        if (sfConfig::get('app_a_stylesheet_cache_http', false))
        {
          $url = sfContext::getRequest()->getUriPrefix() . $file;
          $fileContent = file_get_contents($url);
        }
        else
        {
          $path = sfConfig::get('sf_web_dir') . $file;
          $fileContent = file_get_contents($path);
        }
        if ($type === 'stylesheets')
        {
          $options = array();
          if (!is_null($path))
          {
            // Rewrite relative URLs in CSS files.
            // This trick is available only when we don't insist on
            // pulling our CSS files via http rather than the filesystem
            
            // dirname would resolve symbolic links, we don't want that
            $fdir = preg_replace('/\/[^\/]*$/', '', $path);
            $options['currentDir'] = $fdir;
            $options['docRoot'] = sfConfig::get('sf_web_dir');
          }
          if (sfConfig::get('app_a_minify', false))
          {
            $fileContent = Minify_CSS::minify($fileContent, $options);
          }
        }
        else
        {
          // Trailing carriage return makes behavior more consistent with
          // JavaScript's behavior when loading separate files. For instance,
          // a missing trailing semicolon should be tolerated to the same
          // degree it would be with separate files. The minifier is not
          // a lint tool and should not surprise you with breakage
          $fileContent = JSMin::minify($fileContent) . "\n";
        }
        $content .= $fileContent;
      }
      file_put_contents($dir . '/' . $groupFilename . '.tmp', $content);
      @rename($dir . '/' . $groupFilename . '.tmp', $dir . '/' . $groupFilename);
    }
    $options = json_decode($optionsJson, true);
    // Use stylesheet_path and javascript_path so we can respect relative_root_dir
    if ($type === 'stylesheets')
    {
      $options['href'] = stylesheet_path(sfConfig::get('app_a_assetCacheUrl', '/uploads/asset-cache') . '/' . $groupFilename);
      $html .= tag('link', $options);
    }
    else
    {
      $options['src'] = javascript_path(sfConfig::get('app_a_assetCacheUrl', '/uploads/asset-cache') . '/' . $groupFilename);
      $html .= content_tag('script', '', $options); 
    }
  }
  return $html;
}

function less_include_stylesheets()
{
  echo(less_get_stylesheets());
}

function less_include_javascripts()
{
  echo(less_get_javascripts());
}

