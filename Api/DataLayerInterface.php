<?php

namespace Taggrs\DataLayer\Api;

interface DataLayerInterface
{
    /**
     * Get the event name
     *
     * @return string the event name
     */
    public function getEvent(): string;

    /**
     * Get the e-commerce Data Layer
     *
     * @return array containing the e-commerce data
     */
    public function getEcommerce(): array;

    /**
     * Get the user_data Data Layer
     *
     * @return array containing the user data
     */
    public function getUserData(): array;

    /**
     * Get the Data Layer as an associative array
     *
     * @return array
     */
    public function getDataLayer(): array;
}
