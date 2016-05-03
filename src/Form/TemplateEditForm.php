<?php

namespace Drupal\courier\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\courier\Ajax\CourierTemplate;
use Drupal\courier\TemplateCollectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Drupal\courier\Service\CourierManagerInterface;

/**
 * Create a message.
 */
class TemplateEditForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * The courier manager.
   *
   * @var \Drupal\courier\Service\CourierManagerInterface
   */
  protected $courierManager;

  /**
   * Constructs a MessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   * @param \Drupal\courier\Service\CourierManagerInterface $courier_manager
   *   The courier manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, IdentityChannelManagerInterface $identity_channel_manager, CourierManagerInterface $courier_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->identityChannelManager = $identity_channel_manager;
    $this->courierManager = $courier_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.identity_channel'),
      $container->get('courier.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'courier_template_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TemplateCollectionInterface $template_collection = NULL, ContentEntityTypeInterface $courier_channel = NULL) {
    if (!isset($template_collection) || !isset($courier_channel)) {
      throw new \Exception('Missing template collection or courier channel.');
    }

    if (!$message = $template_collection->getTemplate($courier_channel->id())) {
      // Create it if it does not exist.
      /** @var \Drupal\courier\ChannelInterface $message */
      $message = $this->entityTypeManager
        ->getStorage($courier_channel->id())
        ->create();

      // Saving the template collection will auto save the message entity.
      $template_collection
        ->setTemplate($message)
        ->save();
    }

    $form_state->set('message_entity', $message);
    $form_state->set('template_collection', $template_collection);

    $t_args = [
      '@channel' => $message->getEntityType()->getLabel(),
    ];

    // Entity form display.
    $display = entity_get_form_display($message->getEntityTypeId(), $message->getEntityTypeId(), 'default');
    $form_state->set(['form_display'], $display);
    $form['message'] = ['#tree' => TRUE];
    $display->buildForm($message, $form['message'], $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#attributes' => ['class' => ['use-ajax-submit']],
      '#type' => 'submit',
      '#value' => t('Save @channel', $t_args),
      '#button_type' => 'primary',
    ];
    $form['actions']['close'] = [
      '#attributes' => ['class' => ['use-ajax-submit']],
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => ['::cancelForm'],
    ];

    return $form;
  }

  /**
   * Cancels the form.
   *    * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $form_state->get('message_entity');
    /** @var TemplateCollectionInterface $template_collection */
    $template_collection = $form_state->get('template_collection');
    $response = new AjaxResponse();
    $response
      ->addCommand(new CourierTemplate(
        $template_collection->id(),
        $message->getEntityTypeId(),
        'close'
      ));
    $form_state->setResponse($response);
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $form_state->get('message_entity');
    $form_state->get(['form_display'])
      ->validateFormValues($message, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $form_state->get('message_entity');
    /** @var TemplateCollectionInterface $template_collection */
    $template_collection = $form_state->get('template_collection');

    $form_state->get(['form_display'])
      ->extractFormValues($message, $form, $form_state);
    $message->save();

    $response = new AjaxResponse();
    $response
      ->addCommand(new CourierTemplate(
        $template_collection->id(),
        $message->getEntityTypeId(),
        'close'
      ));
    $form_state->setResponse($response);
  }

}
