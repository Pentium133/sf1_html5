<?php

require_once 'autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins(
      'sfDoctrinePlugin',
      'sfDoctrineGuardPlugin',
      'sfAdminDashPlugin',
      'sfTaskExtraPlugin',
      'frLessPlugin',
      'sfCacheTaggingPlugin',
      'sfFirePHPPlugin',
      'sfFormExtraPlugin',
      'sfImageTransformPlugin',
      'sfImageTransformExtraPlugin',
      'sfWebBrowserPlugin',
      'swFunctionalTestGenerationPlugin'
    );
  }

  public function configureDoctrine(Doctrine_Manager $manager)
  {
    $manager->setCharset('utf8');
    $manager->setCollate('utf8_unicode_ci');

    sfConfig::set(
      'doctrine_model_builder_options',
      array('baseClassName' => 'sfCachetaggableDoctrineRecord')
    );
  }
}
