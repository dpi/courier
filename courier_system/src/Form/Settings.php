<?php

namespace Drupal\courier_system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element;
use Drupal\courier\Entity\CourierContext;
use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\courier\Service\CourierManagerInterface;
use Drupal\courier\TemplateCollectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Courier System settings.
 */
class Settings extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The courier manager.
   *
   * @var \Drupal\courier\Service\CourierManagerInterface
   */
  protected $courierManager;

  /**
   * Constructs a configuration form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\courier\Service\CourierManagerInterface $courier_manager
   *   The courier manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, CourierManagerInterface $courier_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
    $this->courierManager = $courier_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('courier.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'courier_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'courier_system.settings',
    ];
  }

  /**
   * Get mail supported by courier_system.
   *
   * @return array
   *   Mail IDs keyed by module name.
   */
  protected function getSystemMails() {
    $options['user'] = [
      'user_cancel_confirm' => [
        'title' => $this->t('Account cancellation confirmation'),
        'description' => $this->t('Sent to users when they attempt to cancel their accounts.'),
      ],
      'user_password_reset' => [
        'title' => $this->t('Notify user when password reset'),
        'description' => $this->t('Sent to users who request a new password.'),
      ],
      'user_status_activated' => [
        'title' => $this->t('Notify user when account is activated'),
        'description' => $this->t('Sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required)'),
      ],
      'user_status_blocked' => [
        'title' => $this->t('Account blocked'),
        'description' => $this->t('Sent to users when their accounts are blocked.'),
      ],
      'user_status_canceled' => [
        'title' => $this->t('Account canceled'),
        'description' => $this->t('Sent to users when they attempt to cancel their accounts.'),
      ],
      'user_register_admin_created' => [
        'title' => $this->t('Welcome (new user created by administrator)'),
        'description' => $this->t('Sent to new member accounts created by an administrator.'),
      ],
      'user_register_no_approval_required' => [
        'title' => $this->t('Welcome (no approval required)'),
        'description' => $this->t('Sent to new members upon registering, when no administrator approval is required.'),
      ],
      'user_register_pending_approval' => [
        'title' => $this->t('Welcome (awaiting approval)'),
        'description' => $this->t('Sent to new members upon registering, when administrative approval is required.'),
      ],
    ];
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('courier_system.settings');
    $override = $config->get('override');

    // Actions.
    $form['actions'] = [
      '#type' => 'details',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#open' => TRUE,
    ];
    $form['actions']['operation'] = [
      '#title' => $this->t('With selection'),
      '#type' => 'select',
      '#options' => [
        'copy_email' => $this->t('Copy Drupal email to Courier'),
        'enable' => $this->t('Override Drupal email (enable selected)'),
        'disable' => $this->t('Restore Drupal email (disable selected)'),
        'delete' => $this->t('Delete'),
      ],
      '#empty_option' => $this->t(' - Select - '),
      '#button_type' => 'primary',
    ];
    $form['actions']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#button_type' => 'primary',
    ];

    // List items.
    $form['list'] = [
      '#type' => 'courier_template_collection_list',
      '#title' => $this->t('Replace Drupal mails'),
      '#checkboxes' => TRUE,
      '#items' => [],
    ];

    $header = [
      $this->t('Module'),
      $this->t('Description'),
    ];

    $form['add_missing'] = [
      '#type' => 'details',
      '#title' => $this->t('Add missing messages'),
    ];
    $form['add_missing']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE,
      '#tableselect' => TRUE,
      '#multiple' => TRUE,
      '#empty' => $this->t('No messages are missing.'),
    ];
    $form['add_missing']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create messages'),
      '#submit' => [
        [$this, 'submitCreateMessages'],
      ],
    ];

    foreach ($this->getSystemMails() as $module => $mails) {
      foreach ($mails as $mail_id => $definition) {
        $gtc_id = 'courier_system.' . $mail_id;
        if ($gtc = GlobalTemplateCollection::load($gtc_id)) {
          $template_collection = $gtc->getTemplateCollection();
          $form['list']['#items'][$mail_id] = [
            '#title' => $this->t('@module: @title (@status)', [
              '@title' => $definition['title'],
              '@module' => \Drupal::moduleHandler()->getName($module),
              '@status' => !empty($override[$mail_id]) ? $this->t('enabled - using Courier') : $this->t('disabled - using Drupal'),
            ]),
            '#description' => $definition['description'],
            '#template_collection' => $template_collection,
          ];
        }
        else {
          $row = [];
          $row['module']['#markup'] = \Drupal::moduleHandler()->getName($module);
          $row['title']['#markup'] = $definition['title'];
          $form['add_missing']['table'][$mail_id] = $row;
        }
      }
    }

    $form['add_missing']['#open'] = !count($form['list']['#items']);
    if ($count = count(Element::children($form['add_missing']['table']))) {
      $form['add_missing']['#title'] = $this->t('Add missing messages (@count)', ['@count' => $count]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $this->t('No operations were executed.');
    $config = $this->config('courier_system.settings');

    // Template collections keyed by mail ID.
    /** @var \Drupal\courier\Entity\GlobalTemplateCollectionInterface[] $global_template_collections */
    $global_template_collections = [];
    foreach ($this->getSystemMails() as $module => $mails) {
      foreach ($mails as $mail_id => $definition) {
        $gtc = GlobalTemplateCollection::load('courier_system.' . $mail_id);
        if ($gtc) {
          $global_template_collections[$mail_id] = $gtc;
        }
      }
    }

    // List of checked mail IDs.
    $checkboxes = [];
    foreach ($form_state->getValue(['list', 'checkboxes']) as $id => $checked) {
      if ($checked) {
        $checkboxes[] = $id;
      }
    }

    $operation = $form_state->getValue('operation');
    $override = $config->get('override');
    foreach ($checkboxes as $mail_id) {
      if (isset($global_template_collections[$mail_id])) {
        $gtc = $global_template_collections[$mail_id];
        $template_collection = $gtc->getTemplateCollection();

        if (in_array($operation, ['enable', 'disable'])) {
          $enable = $operation == 'enable';
          $override[$mail_id] = $enable;
          $message = $enable ? $this->t('Messages enabled.') : $this->t('Messages disabled.');
        }
        elseif ($operation == 'delete') {
          $message = $this->t('Messages deleted');
          $gtc->delete();
          $template_collection->delete();
          unset($override[$mail_id]);
        }
        elseif ($operation == 'copy_email') {
          $this->copyCoreToCourierEmail($template_collection, $mail_id);
          $template_collection->save();
          $message = $this->t('Messages copied from Drupal to Courier.');
        }
      }
    }

    $config
      ->set('override', $override)
      ->save();

    drupal_set_message($message);
  }

  /**
   * Submit handler for create messages table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitCreateMessages(array &$form, FormStateInterface $form_state) {
    $config = $this->config('courier_system.settings');
    $override = $config->get('override');

    // Create template collections.
    foreach ($form_state->getValue('table') as $mail_id => $create) {
      if ($create) {
        $values = ['id' => 'courier_system.' . $mail_id];
        $gtc = GlobalTemplateCollection::create($values);
        $gtc->save();

        $template_collection = $gtc->getTemplateCollection();

        // Owner.
        // @todo set owner when DER can reference configs.
        // See issue: https://www.drupal.org/node/2555027
        // Context.
        // Create global context for accounts if it does not exist.
        /** @var \Drupal\courier\CourierContextInterface $courier_context */
        if (!$courier_context = CourierContext::load('courier_system_user')) {
          $courier_context = CourierContext::create([
            'label' => t('Courier System: Account'),
            'id' => 'courier_system_user',
            'tokens' => ['user'],
          ]);
          $courier_context->save();
        }
        $template_collection->setContext($courier_context);

        // Contents.
        $this->copyCoreToCourierEmail($template_collection, $mail_id);
        $template_collection->save();

        $override[$mail_id] = TRUE;
      }
    }

    $config
      ->set('override', $override)
      ->save();
  }

  /**
   * Copy email contents from Drupal to Courier email templates.
   *
   * Template collection and email template must be created prior to calling.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A template collection entity.
   * @param string $mail_id
   *   A mail ID as defined in $this->getSystemMails().
   */
  protected function copyCoreToCourierEmail(TemplateCollectionInterface &$template_collection, $mail_id) {
    // Only user is supported at this time.
    $key = substr($mail_id, strlen('user_'));
    $user_mails = $this->config('user.mail');
    $mail = $user_mails->get($key);

    /** @var \Drupal\courier\Entity\Email $courier_email */
    if ($courier_email = $template_collection->getTemplate('courier_email')) {
      foreach ($mail as &$value) {
        $value = nl2br($value);
        $value = str_replace('[user:name]', '[identity:label]', $value);
      }

      $courier_email
        ->setSubject($mail['subject'])
        ->setBody($mail['body'])
        ->save();

      $template_collection->setTemplate($courier_email);
    }
  }

}
