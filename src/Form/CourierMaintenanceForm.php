<?php

/**
 * @file
 * Contains \Drupal\courier\Form\CourierMaintenanceForm.
 */

namespace Drupal\courier\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Courier maintenance form.
 */
class CourierMaintenanceForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CourierMaintenanceForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'courier_admin_maintenance';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mqi'] = [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['mqi']['delete_age'] = [
      '#title' => $this->t('Delete old messages'),
      '#description' => $this->t('This is used to delete messages queue items that have gotten stuck in the queue.'),
      '#type' => 'number',
      '#field_prefix' => $this->t('Delete message older than'),
      '#field_suffix' => $this->t('seconds'),
      '#min' => 1,
      '#default_value' => 60 * 60 * 24,
    ];

    $form['mqi']['actions'] = [
      '#type' => 'actions',
    ];
    $form['mqi']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete messages'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $time = REQUEST_TIME - $form_state->getValue('delete_age');

    $storage = $this->entityTypeManager
      ->getStorage('courier_message_queue_item');
    $ids = $storage->getQuery()
      ->condition('created', $time, '<')
      ->execute();
    $storage->delete($storage->loadMultiple($ids));

    drupal_set_message($this->formatPlural(count($ids), '@count message deleted.', '@count messages deleted.'));
  }

}
