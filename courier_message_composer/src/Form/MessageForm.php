<?php

namespace Drupal\courier_message_composer\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\courier\CourierTokenElementTrait;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\courier\MessageQueueItemInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Drupal\courier\Service\CourierManagerInterface;

/**
 * Create a message.
 */
class MessageForm extends FormBase {

  use CourierTokenElementTrait;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManager
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   * @param \Drupal\courier\Service\CourierManagerInterface $courier_manager
   *   The courier manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, IdentityChannelManagerInterface $identity_channel_manager, CourierManagerInterface $courier_manager) {
    $this->entityManager = $entity_manager;
    $this->identityChannelManager = $identity_channel_manager;
    $this->courierManager = $courier_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.identity_channel'),
      $container->get('courier.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'courier_message_composer_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityTypeInterface $courier_channel = NULL) {
    $form['#title'] = $courier_channel->getLabel();
    $t_args = [
      '@channel' => $courier_channel->getLabel(),
    ];

    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $this->entityManager->getStorage($courier_channel->id())->create();
    $form_state->set('message_entity', $message);

    // Identity.
    $form['identity_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Recipient'),
      '#description' => $this->t('Select a recipient for the message.'),
      '#open' => TRUE,
    ];
    $form['identity_information']['identity'] = [
      '#type' => 'radios',
      '#options' => NULL,
      '#title' => $this->t('Recipient'),
      '#required' => TRUE,
    ];

    $channels = $this->identityChannelManager->getChannels();
    foreach ($channels[$courier_channel->id()] as $entity_type_id) {
      $permission = 'courier_message_composer compose ' . $courier_channel->id() . ' to ' . $entity_type_id;
      if (!$this->currentUser()->hasPermission($permission)) {
        continue;
      }

      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $form['identity_information']['identity'][$entity_type_id] = [
        '#prefix' => '<div class="form-item container-inline">',
        '#suffix' => '</div>',
      ];
      $form['identity_information']['identity'][$entity_type_id]['radio'] = [
        '#type' => 'radio',
        '#title' => $entity_type->getLabel(),
        '#return_value' => "$entity_type_id:*",
        '#parents' => ['identity'],
        '#default_value' => $entity_type_id == 'user' ?: '',
        '#error_no_message' => TRUE,
      ];
      $form['identity_information']['identity'][$entity_type_id]['autocomplete'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $entity_type->getLabel(),
        '#title_display' => 'invisible',
        '#target_type' => $entity_type_id,
        '#tags' => FALSE,
        '#parents' => ['entity', $entity_type_id],
      ];
      if ($entity_type_id == 'user' && !$this->currentUser()->isAnonymous()) {
        $user = User::load($this->currentUser()->id());
        $form['identity_information']['identity'][$entity_type_id]['autocomplete']['#default_value'] = $user;
      }
    }

    // Form display.
    $display = entity_get_form_display($courier_channel->id(), $courier_channel->id(), 'default');
    $form_state->set(['form_display'], $display);
    $form['message'] = [
      '#tree' => TRUE,
    ];
    $display->buildForm($message, $form['message'], $form_state);

    // Tokens.
    $form['tokens'] = [
      '#type' => 'container',
      '#title' => $this->t('Tokens'),
      '#weight' => 51,
    ];
    $form['tokens']['list'] = $this->courierTokenElement();

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Send @channel', $t_args),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $form_state->get('message_entity');
    $form_state->get(['form_display'])
      ->validateFormValues($message, $form, $form_state);

    // Identity.
    if ($form_state->getValue('identity')) {
      $identity = NULL;
      list($entity_type, $entity_id) = explode(':', $form_state->getValue('identity'));
      if (!empty($entity_type)) {
        $references = $form_state->getValue('entity');
        if (is_numeric($references[$entity_type])) {
          $entity_id = $references[$entity_type];
          $identity = $this->entityManager
            ->getStorage($entity_type)
            ->load($entity_id);
        }
      }

      if ($identity instanceof EntityInterface) {
        $form_state->setValueForElement($form['identity_information']['identity'], $identity);
        $channel_types = $this->identityChannelManager->getChannelsForIdentity($identity);
        if (!in_array($message->getEntityTypeId(), $channel_types)) {
          $form_state->setError($form['identity_information']['identity'], $this->t('@identity cannot receive @channel.', [
            '@identity' => $identity->label(),
            '@channel' => $message->getEntityType()->getLabel(),
          ]));
        }
      }
      else {
        $form_state->setError($form['identity_information']['identity'], $this->t('Invalid identity.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\courier\ChannelInterface $message */
    $message = $form_state->get('message_entity');
    $form_state->get(['form_display'])
      ->extractFormValues($message, $form, $form_state);
    $template_collection = TemplateCollection::create()
      ->setTemplate($message);
    $identity = $form_state->getValue('identity');
    $mqi = $this->courierManager->sendMessage($template_collection, $identity);
    if ($mqi instanceof MessageQueueItemInterface) {
      drupal_set_message(t('Message queued for delivery.'));
    }
    else {
      drupal_set_message(t('Failed to send message'), 'error');
    }
  }

}
