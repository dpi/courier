<?php

namespace Drupal\courier\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\courier\CourierTokenElementTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\courier\Ajax\CourierTemplate;
use Drupal\courier\Form\TemplateEditForm;
use Drupal\courier\TemplateCollectionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for channels.
 */
class ChannelFormController extends ControllerBase implements ContainerInjectionInterface {

  use CourierTokenElementTrait;

  /**
   * Gets the template form for a channel in a template collection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\courier\TemplateCollectionInterface $courier_template_collection
   *   A template collection entity.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $courier_channel
   *   Entity type definition for the channel.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   A render array for regular requests, or AjaxResponse if called by ajax.
   */
  public function template(Request $request, TemplateCollectionInterface $courier_template_collection, ContentEntityTypeInterface $courier_channel) {
    $template_collection = $courier_template_collection;
    $render = \Drupal::formBuilder()
      ->getForm(TemplateEditForm::class, $template_collection, $courier_channel);

    if ($request->request->get('js')) {
      $selector = '.template_collection[template_collection=' . $template_collection->id() . '] .editor.' . $courier_channel->id();
      $response = new AjaxResponse();
      $response
        ->addCommand(new HtmlCommand($selector, $render))
        ->addCommand(new PrependCommand($selector, ['#type' => 'status_messages']))
        ->addCommand(new CourierTemplate(
          $template_collection->id(),
          $courier_channel->id(),
          'open'
        ));
      return $response;
    }

    return $render;
  }

  /**
   * Get tokens for a template collection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\courier\TemplateCollectionInterface $courier_template_collection
   *   A template collection entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *    A AJAX response object.
   */
  public function tokens(Request $request, TemplateCollectionInterface $courier_template_collection) {
    $template_collection = $courier_template_collection;

    $render['tokens'] = [
      '#type' => 'container',
    ];
    $render['tokens']['list'] = $this->templateCollectionTokenElement($template_collection);

    if ($request->request->get('js')) {
      $selector = '.template_collection[template_collection=' . $template_collection->id() . '] .properties_container';
      $response = new AjaxResponse();
      $response
        ->addCommand(new HtmlCommand($selector, $render));
      return $response;
    }

    return $render;
  }

}
