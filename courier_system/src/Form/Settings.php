<?php

/**
 * @file
 * Contains \Drupal\courier_system\Form\Settings.
 */

namespace Drupal\courier_system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\courier\Entity\CourierContext;
use Drupal\courier\Entity\TemplateCollection;
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
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The courier manager.
   *
   * @var CourierManagerInterface
   */
  protected $courierManager;

  /**
   * Constructs a configuration form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param CourierManagerInterface $courier_manager
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
      'user_cancel_confirm' => $this->t('Account cancellation confirmation'),
      'user_password_reset' => $this->t('Notify user when password reset'),
      'user_status_activated' => $this->t('Notify user when account is activated'),
      'user_status_blocked' => $this->t('Account blocked'),
      'user_status_canceled' => $this->t('Account canceled'),
      'user_register_admin_created' => $this->t('Welcome (new user created by administrator)'),
      'user_register_no_approval_required' => $this->t('Welcome (no approval required)'),
      'user_register_pending_approval' => $this->t('Welcome (awaiting approval)'),
    ];
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('courier_system.settings');
    $master = $config->get('master');
    $override = $config->get('override');

    $template_collection_ids = \Drupal::state()->get('courier_system_template_collections', []);

    $form['master'] = [
      '#title' => $this->t('Master enable'),
      '#type' => 'checkbox',
      '#default_value' => !empty($master),
    ];

    $header = [
      'override' => [
        'data' => $this->t('Override System'),
        'class' => ['checkbox'],
      ],
      $this->t('Module'),
      $this->t('Description'),
      $this->t('Template Collection ID'),
      'copy_core' => [
        'data' => $this->t('Copy core to mail template'),
        'class' => ['checkbox'],
      ],
    ];

    $form['override'] = [
      '#title' => $this->t('Replace Drupal mails'),
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE,
    ];

    foreach ($this->getSystemMails() as $module => $mails) {
      foreach ($mails as $mail_id => $title) {
        $t_args = [
          '@module' => $module,
          '@id' => $mail_id,
        ];
        $row = [];

        $row['override'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Override @id', $t_args),
          '#title_display' => 'invisible',
          '#default_value' => !empty($override[$mail_id]),
          '#wrapper_attributes' => [
            'class' => [
              'checkbox',
            ],
          ],
        ];
        $row['cat']['#markup'] = $module;
        $row['title']['#markup'] = $title;

        if (array_key_exists($mail_id, $template_collection_ids)) {
          $row['template_collection']['#markup'] = $template_collection_ids[$mail_id];
        }
        else {
          $row['template_collection']['#markup'] = $this->t('None');
        }

        $row['copy_core'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Copy core email to Courier email template', $t_args),
          '#title_display' => 'invisible',
          '#default_value' => FALSE,
          '#wrapper_attributes' => [
            'class' => [
              'checkbox',
            ],
          ],
        ];

        $form['override'][$mail_id] = $row;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mails = $this->getSystemMails();
    $template_collection_ids = \Drupal::state()->get('courier_system_template_collections', []);
    $config = $this->config('courier_system.settings');
    $override = [];
    foreach ($form_state->getValue('override') as $mail_id => $row) {
      if ($override[$mail_id] = !empty($row['override'])) {
        $copy_core = !empty($row['copy_core']);

        // Create Template Collection.
        if (array_key_exists($mail_id, $template_collection_ids)) {
          $template_collection = TemplateCollection::load($template_collection_ids[$mail_id]);
        }
        else {
          $copy_core = TRUE;
          $template_collection = TemplateCollection::create();

          // Create global context for accounts if it does not exist.
          if (!$courier_context = CourierContext::load('courier_system_user')) {
            $courier_context = CourierContext::create([
              'label' => t('Courier System: Account'),
              'id' => 'courier_system_user',
              'tokens' => ['user']
            ]);
            $courier_context->save();
          }
          $template_collection->setContext($courier_context);

          // @todo set owner when DER can reference configs.
          // See issue: https://www.drupal.org/node/2555027
          if ($template_collection->save()) {
            $template_collection_ids[$mail_id] = $template_collection->id();
            $this->courierManager->addTemplates($template_collection);
            $template_collection->save();
          }
        }

        if ($copy_core && $template_collection instanceof TemplateCollectionInterface) {
          // Only user is supported at this time.
          $module = 'user';
          if (array_key_exists($mail_id, $mails[$module])) {
            $key = substr($mail_id, strlen($module . '_'));
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
            }
          }
        }
      }
    }

    \Drupal::state()->set('courier_system_template_collections', $template_collection_ids);

    $config
      ->set('master', (boolean) $form_state->getValue('master'))
      ->set('override', $override)
      ->save();

    drupal_set_message(t('Settings saved.'));
  }

}
