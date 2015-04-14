<?php

/**
 * @file
 * Contains \Drupal\courier\Form\EmailForm.
 */

namespace Drupal\courier\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\EmailInterface;

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
    $email = $this->entity;

    if (!$email->isNew()) {
      $form['#title'] = $this->t('Edit email');
    }

    return parent::form($form, $form_state, $email);
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
