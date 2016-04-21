<?php

/**
 * @file
 * Contains \Drupal\courier\Controller\ChannelFormController.
 */

namespace Drupal\courier\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\courier\Ajax\CourierTemplate;
use Drupal\courier\Form\TemplateEditForm;
use Drupal\courier\TemplateCollectionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for channels.
 */
class ChannelFormController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Gets the template form for a channel in a template collection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The current request.
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

  public function tokens(Request $request, TemplateCollectionInterface $courier_template_collection) {
    $template_collection = $courier_template_collection;

    $render['tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
    ];

    $tokens = ($context = $template_collection->getContext()) ? $context->getTokens() : ['identity'];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $render['tokens']['list'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $tokens,
      ];
    }
    else {
      // Add global token types.
      $token_info = \Drupal::token()->getInfo();
      foreach ($token_info['types'] as $type => $type_info) {
        if (empty($type_info['needs-data'])) {
          $tokens[] = $type;
        }
      }

      $render['tokens']['list'] = [
        '#markup' => $this->t('Available tokens: @token_types', ['@token_types' => implode(', ', $tokens)]),
      ];
    }

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
