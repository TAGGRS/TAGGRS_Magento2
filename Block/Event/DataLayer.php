<?php

namespace Taggrs\DataLayer\Block\Event;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Taggrs\DataLayer\Api\DataLayerInterface;

abstract class DataLayer extends Template implements DataLayerInterface
{

    private Session $customerSession;

    protected array $dataLayer = [];



    public $_template = 'data-layer.phtml';

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
    }


    public function getDataLayer(): array
    {
        return $this->getDataLayerArray();
    }

    public function getDataLayerArray(): array
    {
        return [
            'event' => $this->getEvent(),
            'ecommerce' => $this->getEcommerce(),
        ];
    }

    public function getUserData(): array
    {
        $email = '';
        $hashedEmail = '';

        if ($this->customerSession->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();
            $hashedEmail =  hash('sha256', $email);
        }

        return [
            'email_hashed' => $hashedEmail,
            'email' => $email,
        ];
    }
}
