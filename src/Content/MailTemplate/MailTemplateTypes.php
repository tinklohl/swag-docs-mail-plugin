<?php

namespace DemoMailPlugin\Content\MailTemplate;

use DemoMailPlugin\Event\CustomerChangedPaymentMethodCustomerNotificationEvent;
use DemoMailPlugin\Event\CustomerChangedPaymentMethodShopOwnerNotificationEvent;

class MailTemplateTypes
{
    public const MAILTYPE_CUSTOMER_PAYMENT_METHOD_CHANGED_CUSTOMER = CustomerChangedPaymentMethodCustomerNotificationEvent::EVENT_NAME;
    public const MAILTYPE_CUSTOMER_PAYMENT_METHOD_CHANGED_SHOP_OWNER = CustomerChangedPaymentMethodShopOwnerNotificationEvent::EVENT_NAME;
}
