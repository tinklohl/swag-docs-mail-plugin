<?php

namespace DemoMailPlugin\Event;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerChangedPaymentMethodEvent;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\MailActionInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Class CustomerChangedPaymentMethodEvent
 * Custom event extending the ChangedPaymentMethodEvent by Shopware
 * Events that trigger mails must implement MailActionInterface
 */
class CustomerChangedPaymentMethodCustomerNotificationEvent extends CustomerChangedPaymentMethodEvent implements MailActionInterface
{
    // custom event name, so it does not conflict with the parent event name
    public const EVENT_NAME = 'customer.changed-payment-method.customer-mail';

    /**
     * @var MailRecipientStruct
     */
    private $mailRecipientStruct;

    /**
     * @var PaymentMethodEntity
     */
    private $newPaymentMethod;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer,
        RequestDataBag $requestDataBag,
        PaymentMethodEntity $newPaymentMethod
    ) {
        parent::__construct($salesChannelContext, $customer, $requestDataBag);

        $this->newPaymentMethod = $newPaymentMethod;

        // set mail recipient to customer
        $this->mailRecipientStruct = new MailRecipientStruct(
            [
                $this->getCustomer()->getEmail() => $this->getCustomer()->getFirstName() . ' ' . $this->getCustomer()->getLastName()
            ]
        );
    }

    /**
     * Add the "newPaymentMethod" key to the parents availableData, so it can be displayed in the email template
     */
    public static function getAvailableData(): EventDataCollection
    {
        return parent::getAvailableData()
            ->add('oldPaymentMethod', new EntityType(PaymentMethodDefinition::class))
            ->add('newPaymentMethod', new EntityType(PaymentMethodDefinition::class));
    }

    /**
     * override parents method, so it returns custom event name,
     * and does not conflict with standard shopware event name
     */
    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->getSalesChannelContext()->getSalesChannel()->getId();
    }

    public function getNewPaymentMethod(): PaymentMethodEntity
    {
        return $this->newPaymentMethod;
    }

    public function getOldPaymentMethod(): PaymentMethodEntity
    {
        return $this->getCustomer()->getDefaultPaymentMethod();
    }
}
