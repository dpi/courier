<?php

/**
 * @file
 * Contains \Drupal\courier\Form\EmailForm.
 */

namespace Drupal\courier\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\EmailInterface;
use Drupal\courier\Entity\TemplateCollection;

/**
 * Form controller for email.
 */
class EmailForm extends ContentEntityForm {

  /**
   * The courier_email entity.
   *
   * @var \Drupal\courier\EmailInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, EmailInterface $email = NULL) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\courier\Entity\Email $email */
    $email = $this->entity;

    if (!$email->isNew()) {
      $form['#title'] = $this->t('Edit email');
    }

    $form['tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
      '#weight' => 51,
    ];
    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($email);

    $tokens = ($context = $template_collection->getContext()) ? $context->getTokens() : ['identity'];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens']['list'] = [
        '#theme' => 'token_tree',
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

      $form['tokens']['list'] = [
        '#markup' => $this->t('Available tokens: @token_types', ['@token_types' => implode(', ', $tokens)]),
      ];
    }

    $form['tokens']['help']['#markup'] = '<p>' . $this->t('Tokens are replaced in subject and body fields.') . '</p>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $email = $this->entity;
    $is_new = $email->isNew();
    $email->save();

    $t_args = array('%label' => $email->label());
    if ($is_new) {
      drupal_set_message(t('Email %label has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Email %label was updated.', $t_args));
    }
  }

}
