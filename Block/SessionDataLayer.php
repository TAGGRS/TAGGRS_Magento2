<?php

namespace Taggrs\DataLayer\Block;

use Magento\Customer\Model\Session;
use Taggrs\DataLayer\Helper\UserDataHelper;
use Magento\Framework\View\Element\Template;

/**
 * Renders Data Layer from session
 */
class SessionDataLayer extends DataLayer
{
    /**
     * @var Session customer session
     */
    private Session $session;

    /**
     * Class constructor
     *
     * @param Session $session
     * @param UserDataHelper $userDataHelper
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Session          $session,
        UserDataHelper   $userDataHelper,
        Template\Context $context,
        array            $data = []
    ) {
        parent::__construct($userDataHelper, $context, $data);

        $this->session = $session;
    }

    /**
     * Get the Data Layer as an associative array
     *
     * @return array the Data Layer
     */
    public function getDataLayer(): array
    {
        if ($this->session->getDataLayer()) {
            $dataLayer = $this->session->getDataLayer();
            $this->session->unsDataLayer();
            return $dataLayer;
        }

        return [];
    }

    /**
     * Get Data Layer as JSON-encoded object
     *
     * @return string JSON-encoded Data Layer
     */
    public function getDataLayerJson(): string
    {
        return json_encode($this->getDataLayer());
    }

    /**
     * Get the event name. Override this method in child classes.
     *
     * @return string event name
     */
    public function getEvent(): string
    {
        return '';
    }

    /**
     * Get the e-commerce Data Layer. Override this method in child classes.
     *
     * @return array e-commerce Data Layer
     */
    public function getEcommerce(): array
    {
        return [];
    }
}
