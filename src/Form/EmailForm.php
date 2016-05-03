<?php

namespace Drupal\courier\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\EmailInterface;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\courier\CourierTokenElementTrait;

/**
 * Form controller for email.
 */
class EmailForm extends ContentEntityForm {

  use CourierTokenElementTrait;

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

    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($email);
    $form['tokens'] = [
      '#type' => 'container',
      '#weight' => 51,
    ];

    $form['tokens']['list'] = $this->templateCollectionTokenElement($template_collection);
    $form['tokens']['help']['#prefix'] = '<p>';
    $form['tokens']['help']['#markup'] = $this->t('Tokens are replaced in subject and body fields.');
    $form['tokens']['help']['#suffix'] = '</p>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $email = $this->entity;
    $is_new = $email->isNew();
    $email->save();

    $t_args = ['%label' => $email->label()];
    if ($is_new) {
      drupal_set_message(t('Email %label has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Email %label was updated.', $t_args));
    }
  }

}
