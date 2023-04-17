<?php

namespace Drupal\custom_actions\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action that can save any entity and send mail notification.
 *
 * @Action(
 *   id = "entity:save_and_send_mail_custom_action",
 *   action_label = @Translation("Save and send mail"),
 *   deriver = "Drupal\Core\Action\Plugin\Action\Derivative\EntityChangedActionDeriver",
 * )
 */
class SaveAndSendMailCustomAction extends EntityActionBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a SaveAction object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time, MailManagerInterface $mail_manager) {
    EntityActionBase::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->time = $time;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setChangedTime($this->time->getRequestTime())->save();

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
    ];

    $had_sent = $this->mailManager->mail(
      'custom_actions',
      'admin_mail',
      'someuser@test.com',
      'en',
      $params
    );

    $logger = \Drupal::logger('custom_actions');
    if (!$had_sent['result']) {
      $logger->warning('Errors has been occurred during mail sending.');
    }
    else {
      $logger->info('Mail has been sent successfully.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
