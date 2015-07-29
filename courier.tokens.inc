<?php

/**
 * @file
 * Builds tokens for Courier.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_token_info().
 */
function courier_token_info() {
  // Token types.
  $tokens['types']['identity'] = [
    'name' => t('Identities'),
    'description' => t('Tokens for identities.'),
    'needs-data' => 'identity',
  ];

  $tokens['tokens']['identity']['label'] = [
    'name' => t("Label"),
    'description' => t("Label for an identity."),
  ];

  return $tokens;
}

/**
 * Implements hook_tokens().
 */
function courier_tokens($type, $tokens, array $data = [], array $options = []) {
  $replacements = [];

  if ($type == 'identity' && !empty($data['identity'])) {
    if (($identity = $data['identity']) && $identity instanceof EntityInterface) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'label':
            $replacements[$original] = $identity->label() ?: t('Unassigned');
            break;

        }
      }
    }
  }

  return $replacements;
}
