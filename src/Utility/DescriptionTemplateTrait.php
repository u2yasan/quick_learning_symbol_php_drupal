<?php

namespace Drupal\quicklearning_symbol\Utility;

use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait to implement a "drop-in" template for Example's controllers.
 *
 * This is a simple utility trait to allow our example modules to put their
 * explanatory text into a twig template, and pass any variables needed
 * for the template.  By default, the template will be named
 * 'description.html.twig, and should be placed in the module's templates/
 * directory.
 *
 * These templates should be translatable as is usual for Drupal's Twig
 * templates, using the {% trans } and {% endtrans %} tags to block out the
 * text that needs to be passed to the translator. Modules using this
 * trait should:
 *
 *  - Implement the getModuleName() member function.
 *  - Override the getDescriptionVariables() member function in order to
 *    pass variables to Twig needed to render your template.
 *
 * @see \Drupal\Core\Render\Element\InlineTemplate
 * @see https://www.drupal.org/developing/api/8/localization
 */
trait DescriptionTemplateTrait {

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Constructs a description page controller.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   */
  public function __construct(ModuleExtensionList $module_extension_list) {
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * Creates a description page controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A controller instance.
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('extension.list.module'));
  }

  /**
   * Generate a render array with our templated content.
   *
   * @return array
   *   A render array.
   */
  public function description() {
    $template_path = $this->getDescriptionTemplatePath();
    $template = file_get_contents($template_path);
    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $this->getDescriptionVariables(),
      ],
    ];
    return $build;
  }

  /**
   * Name of our module.
   *
   * @return string
   *   A module name.
   */
  abstract protected function getModuleName();

  /**
   * Variables to act as context to the twig template file.
   *
   * @return array
   *   Associative array that defines context for a template.
   */
  protected function getDescriptionVariables() {
    $variables = [
      'module' => $this->getModuleName(),
    ];
    return $variables;
  }

  /**
   * Get full path to the template.
   *
   * @return string
   *   Path string.
   */
  protected function getDescriptionTemplatePath() {
    return $this->moduleExtensionList->getPath($this->getModuleName()) . '/templates/description.html.twig';
  }

}
