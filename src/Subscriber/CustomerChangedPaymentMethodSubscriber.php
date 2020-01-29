<?php

namespace DemoMailPlugin\Subscriber;

use DemoMailPlugin\Event\CustomerChangedPaymentMethodCustomerNotificationEvent;
use DemoMailPlugin\Event\CustomerChangedPaymentMethodShopOwnerNotificationEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerChangedPaymentMethodEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Event\BusinessEventDispatcher;
use Shopware\Core\Framework\Event\BusinessEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerChangedPaymentMethodSubscriber implements EventSubscriberInterface
{
    /**
     * Shopware EventDispatcher based on Symfony EventDispatcher
     *
     * @var BusinessEventDispatcher
     */
    private $dispatcher;

    /**
     * Shopware service to retrieve basic shop data
     *
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    public function __construct(
        BusinessEventDispatcher $dispatcher,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $paymentMethodRepository
    ) {
        $this->dispatcher = $dispatcher;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * Subscribe to Shopware's "customer has changed payment method" event
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BusinessEvents::CHECKOUT_CUSTOMER_CHANGED_PAYMENT_METHOD => 'onCustomerChangedPaymentMethod',
            CustomerChangedPaymentMethodEvent::class => 'onCustomerChangedPaymentMethod'
        ];
    }

    /**
     * Trigger two events to send mail notification to customer and shop owner
     * Inside the first event the customer gets notified about the old and new payment method they chose
     * Inside the second event the shop owner only gets notified about a customer changing their payment method
     */
    public function onCustomerChangedPaymentMethod(CustomerChangedPaymentMethodEvent $event): void
    {
        // get the new payment method entity
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $event->getRequestDataBag()->get('paymentMethodId')));

        $newPaymentMethod = $this->paymentMethodRepository->search($criteria, $event->getContext())->first();

        if ($newPaymentMethod === null) {
            return;
        }

        // event to trigger mail to notify customer about successful change of payment method
        // new payment method will be injected aswell, so we can display it inside the email
        $this->dispatcher->dispatch(
            new CustomerChangedPaymentMethodCustomerNotificationEvent(
                $event->getSalesChannelContext(),
                $event->getCustomer(),
                $event->getRequestDataBag(),
                $newPaymentMethod
            )
        );

        // event to trigger mail to notify shop owner about customer's changed payment method
        $this->dispatcher->dispatch(
            new CustomerChangedPaymentMethodShopOwnerNotificationEvent(
                $event->getSalesChannelContext(),
                $event->getCustomer(),
                $event->getRequestDataBag(),
                $this->systemConfigService // inject systemConfigService to get shop owner email address inside event
            )
        );
    }
}
