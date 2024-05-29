<?php

namespace Taggrs\DataLayer\Api;

interface DataLayerInterface
{
    public function getEvent(): string;

    public function getEcommerce(): array;

    public function getUserData(): array;

    public function getDataLayer(): array;

}
