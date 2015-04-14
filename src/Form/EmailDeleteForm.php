<?php

/**
 * @file
 * Contains \Drupal\courier\Form\EmailDeleteForm.
 */

namespace Drupal\courier\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for deleting an email.
 */
class EmailDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this email?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $this->entity;
    $email->delete();
    drupal_set_message(t('Email deleted.'));
  }

}
