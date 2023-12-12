<?php

namespace Compwright\OAuth2_Quickbooks_Online;

use League\OAuth2\Client\Provider\GenericResourceOwner;

class QuickbooksCompany extends GenericResourceOwner
{
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->resourceOwnerId;
    }

    public function getName(): ?string
    {
        return $this->response['CompanyInfo']['CompanyName'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->response['CompanyInfo']['Email']['Address'] ?? null;
    }

    public function getDomain(): ?string
    {
        return $this->response['CompanyInfo']['Domain'] ?? null;
    }

    public function getCountry(): ?string
    {
        return $this->response['CompanyInfo']['Country'] ?? null;
    }
}
