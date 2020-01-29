<?php declare(strict_types=1);

namespace DemoMailPlugin;

use DemoMailPlugin\Event\CustomerChangedPaymentMethodCustomerNotificationEvent;
use DemoMailPlugin\Event\CustomerChangedPaymentMethodShopOwnerNotificationEvent;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class DemoMailPlugin extends Plugin
{
    private const EVENT_NAMES = [
        CustomerChangedPaymentMethodCustomerNotificationEvent::EVENT_NAME,
        CustomerChangedPaymentMethodShopOwnerNotificationEvent::EVENT_NAME
    ];

    /**
     * Remove default email template types and event actions
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        $connection = $this->container->get(Connection::class);

        foreach (self::EVENT_NAMES as $eventName) {
            $stmt = $connection->prepare('DELETE FROM `event_action` WHERE `event_name` = :eventName');
            $stmt->bindParam(':eventName', $eventName);
            $stmt->execute();

            $stmt = $connection->prepare('DELETE FROM `mail_template_type` WHERE `technical_name` = :eventName');
            $stmt->bindParam(':eventName', $eventName);
            $stmt->execute();
        }
    }
}
