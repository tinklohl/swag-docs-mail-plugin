<?php declare(strict_types=1);

namespace DemoMailPlugin\Migration;

use DemoMailPlugin\Content\MailTemplate\MailTemplateTypes;
use DemoMailPlugin\Event\CustomerChangedPaymentMethodShopOwnerNotificationEvent;
use Doctrine\DBAL\Connection;
use DemoMailPlugin\Event\CustomerChangedPaymentMethodCustomerNotificationEvent;
use Shopware\Core\Content\MailTemplate\MailTemplateActions;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1580215571MailTemplateType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580215571;
    }

    public function update(Connection $connection): void
    {
        $language = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);

        $shopName = $connection->executeQuery('SELECT * FROM `system_config` WHERE `configuration_key` = "core.basicinformation.shopName"')->fetch();
        $shopName = json_decode($shopName['configuration_value'])->_value;

        $definitionMailTypes = $this->getDefinitionMailTypes();

        foreach ($definitionMailTypes as $typeName => $mailType) {
            $availableEntities = null;
            if (array_key_exists('availableEntities', $mailType)) {
                $availableEntities = json_encode($mailType['availableEntities']);
            }

            $connection->insert(
                'mail_template_type',
                [
                    'id' => Uuid::fromHexToBytes($mailType['id']),
                    'technical_name' => $typeName,
                    'available_entities' => $availableEntities,
                    'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'mail_template_type_translation',
                [
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailType['id']),
                    'name' => $mailType['name'],
                    'language_id' => $language,
                    'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'event_action',
                [
                    'id' => Uuid::randomBytes(),
                    'event_name' => $mailType['eventName'],
                    'action_name' => MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION,
                    'config' => json_encode([
                        'mail_template_type_id' => $mailType['id']
                    ]),
                    'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $mailTemplateId = Uuid::randomHex();

            $connection->insert('mail_template', [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailType['id']),
                'system_default' => 0,
                'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $connection->insert('mail_template_translation', [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'language_id' => $language,
                'sender_name' => $shopName,
                'subject' => $mailType['subject'],
                'description' => $mailType['description'],
                'content_html' => $mailType['contentHtml'],
                'content_plain' => $mailType['contentPlain'],
                'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $salesChannels = $connection->fetchAll('SELECT `id` FROM `sales_channel` ');

            foreach ($salesChannels as $salesChannel) {
                $connection->insert('mail_template_sales_channel', [
                    'id' => Uuid::randomBytes(),
                    'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailType['id']),
                    'sales_channel_id' => $salesChannel['id'],
                    'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function getDefinitionMailTypes(): array
    {
        return [
            // customer notification event
            MailTemplateTypes::MAILTYPE_CUSTOMER_PAYMENT_METHOD_CHANGED_CUSTOMER => [
                'id' => Uuid::randomHex(),
                'name' => 'Customer changed payment method: Customer confirmation',
                'availableEntities' => ['customer' => 'customer', 'oldPaymentMethod' => 'payment_method', 'newPaymentMethod' => 'payment_method'],
                'eventName' => CustomerChangedPaymentMethodCustomerNotificationEvent::EVENT_NAME,
                'description' => 'Payment method changed: Customer confirmation',
                'subject' => 'Payment method changed successfully',
                'contentHtml' => $this->getCustomerConfirmationHtml(),
                'contentPlain' => $this->getCustomerConfirmationPlain()
            ],
            // shop owner notification event
            MailTemplateTypes::MAILTYPE_CUSTOMER_PAYMENT_METHOD_CHANGED_SHOP_OWNER => [
                'id' => Uuid::randomHex(),
                'name' => 'Customer changed payment method: Shop owner notification',
                'availableEntities' => ['customer' => 'customer'],
                'eventName' => CustomerChangedPaymentMethodShopOwnerNotificationEvent::EVENT_NAME,
                'description' => 'Payment method changed: Shop owner notification',
                'subject' => 'Customer changed payment method',
                'contentHtml' => $this->getShopOwnerNotificationHtml(),
                'contentPlain' => $this->getShopOwnerNotificationPlain()
            ]
        ];
    }

    private function getShopOwnerNotificationHtml(): string
    {
        return '<div style="font-family:arial; font-size:12px;">
            <p>
                Dear shop owner,<br/>
                <br/>
                {{ customer.firstName }} {{ customer.lastName }} changed his payment method.<br/>
            </p>
        </div>';
    }

    private function getShopOwnerNotificationPlain(): string
    {
        return 'Dear shop owner,

                {{ customer.firstName }} {{ customer.lastName }} changed his payment method.
        ';
    }

    private function getCustomerConfirmationHtml(): string
    {
        return '<div style="font-family:arial; font-size:12px;">
            <p>
                Dear {{ customer.salutation.displayName }} {{ customer.lastName }},<br/>
                <br/>s
                your payment method was changed successfully.<br/>
                Old payment method: {{ oldPaymentMethod.name }}<br/>
                New payment method: {{ newPaymentMethod.name }}</br>
            </p>
        </div>';
    }

    private function getCustomerConfirmationPlain(): string
    {
        return 'Dear {{ customer.salutation.displayName }} {{ customer.lastName }},

                your payment method was changed successfully.
                Old payment method: {{ oldPaymentMethod.name }}
                New payment method: {{ newPaymentMethod.name }}
        ';
    }
}
