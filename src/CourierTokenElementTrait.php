<?php

/**
 * @file
 * Contains \Drupal\courier\CourierTokenElementTrait.
 */

namespace Drupal\courier;

/**
 * Defines a trait for adding a token element based on available tokens for a
 * template collection.
 *
 * Requires \Drupal\Core\StringTranslation\StringTranslationTrait.
 */
trait CourierTokenElementTrait {

  /**
   * Render a token element for a template collection.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A template collection entity.
   *
   * @return array
   *   A render array.
   *
   * @see ::courierTokenElement().
   */
  public function templateCollectionTokenElement(TemplateCollectionInterface $template_collection) {
    $tokens = ($context = $template_collection->getContext()) ? $context->getTokens() : [];
    return $this->courierTokenElement($tokens);
  }

  /**
   * Render a token element.
   *
   * This function will determine if Token module is enabled, and use an AJAX
   * token tree element. Otherwise it will render the list of tokens in plain
   * text.
   *
   * @param array $tokens
   *   An array of global token types.
   *
   * @return array
   *   A render array.
   */
  public function courierTokenElement($tokens = []) {
    if (!in_array('identity', $tokens)) {
      $tokens[] = 'identity';
    }

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      return [
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

      foreach ($tokens as &$token) {
        $token = '[' . $token . ':*]';
      }

      return [
        '#markup' => $this->t('Available tokens: @token_types', ['@token_types' => implode(', ', $tokens)]),
      ];
    }
  }

}
