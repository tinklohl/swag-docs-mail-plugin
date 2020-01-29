<?php

namespace DemoMailPlugin\Event;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerChangedPaymentMethodEvent;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\MailActionInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Class CustomerChangedPaymentMethodEvent
 * Custom event extending the ChangedPaymentMethodEvent by Shopware
 * Events that trigger mails must implement MailActionInterface
 */
class CustomerChangedPaymentMethodShopOwnerNotificationEvent extends CustomerChangedPaymentMethodEvent implements MailActionInterface
{
    // custom event name, so it does not conflict with the parent event name
    public const EVENT_NAME = 'customer.changed-payment-method.shop-owner-mail';

    /**
     * @var MailRecipientStruct
     */
    private $mailRecipientStruct;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer,
        RequestDataBag $requestDataBag,
        SystemConfigService $systemConfigService // needed to retrieve shop owner information
    ) {
        parent::__construct($salesChannelContext, $customer, $requestDataBag);

        // set mail recipient to shop owner
        $this->mailRecipientStruct = new MailRecipientStruct(
            [
                $systemConfigService->get('core.basicInformation.email') => $systemConfigService->get('core.basicInformation.shopName')
            ]
        );
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
}
