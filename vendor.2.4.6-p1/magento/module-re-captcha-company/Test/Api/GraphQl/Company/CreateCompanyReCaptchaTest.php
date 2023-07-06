<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaCompany\Test\Api\GraphQl\Company;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQl test for create company with ReCaptcha enabled.
 */
class CreateCompanyReCaptchaTest extends GraphQlAbstract
{
    /**
     * Test creation of company.
     *
     * @magentoConfigFixture default_store btob/website_configuration/company_active 1
     * @magentoConfigFixture default_store company/general/allow_company_registration 1
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     *
     * @return void
     */
    public function testCreateCompanyAccount(): void
    {
        $mutationQuery = $this->getQuery();

        $this->expectExceptionMessage('ReCaptcha validation failed, please try again');
        $this->graphQlMutation($mutationQuery);
    }

    /**
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
mutation {
  createCompany(
    input: {
      company_name: "Company name"
      company_email: "company@example.com"
      legal_name: "Legal name"
      vat_tax_id: "12345"
      reseller_id: "123"
      company_admin:   {
        email: "company_user@example.com"
        firstname: "Admin"
        lastname: "Company"
        gender: 1
        job_title: "Manager"
      }
      legal_address: {
        city: "Example city"
        country_id: US
        postcode: "12345"
        region: {
            region_id: 35
        }
        street: ["Street  123"]
        telephone: "0123456789"
      }
    }
  ) {
    company {
      id
      email
      name
      legal_name
      vat_id
      reseller_id
      company_admin {
        email
        firstname
        lastname
        gender
        job_title
      }
      legal_address {
        street
        city
        postcode
        country_code
        telephone
        region {
          region_code
          region_id
          region
        }
      }
    }
  }
}
QUERY;
    }
}
