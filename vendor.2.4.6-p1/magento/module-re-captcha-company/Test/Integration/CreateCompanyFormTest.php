<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaCompany\Test\Integration;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaValidation\Model\Validator;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CreateCompanyFormTest extends AbstractController
{
    /**
     * @var MutableScopeConfig
     */
    private $mutableScopeConfig;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ValidationResult|MockObject
     */
    private $recaptchaValidationResultMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mutableScopeConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->companyRepository = $this->_objectManager->get(CompanyRepositoryInterface::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->recaptchaValidationResultMock = $this->createMock(ValidationResult::class);
        $recaptchaValidatorMock = $this->createMock(Validator::class);
        $recaptchaValidatorMock->expects($this->any())
            ->method('isValid')
            ->willReturn($this->recaptchaValidationResultMock);
        $this->_objectManager->addSharedInstance($recaptchaValidatorMock, Validator::class);
    }

    /**
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     */
    public function testGetRequestIfReCaptchaIsDisabled(): void
    {
        $this->setConfig(false, 'test_public_key', 'test_private_key');

        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testGetRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->setConfig(true, null, null);

        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website  recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website  recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website  recaptcha_frontend/type_for/company_create invisible
     *
     * This fixture below is necessary for proper behavior of "ifconfig" in the layout during test run
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/company_create invisible
     */
    public function testGetRequestIfReCaptchaIsEnabled(): void
    {
        $this->setConfig(true, 'test_public_key', 'test_private_key');

        $this->checkSuccessfulGetResponse(true);
    }

    /**
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     */
    public function testPostRequestIfReCaptchaIsDisabled(): void
    {
        $this->setConfig(false, 'test_public_key', 'test_private_key');

        $this->checkSuccessfulPostResponse();
    }

    /**
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testPostRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->setConfig(true, null, null);

        $this->checkSuccessfulPostResponse();
    }

    /**
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testPostRequestWithSuccessfulReCaptchaValidation(): void
    {
        $this->setConfig(true, 'test_public_key', 'test_private_key');
        $this->recaptchaValidationResultMock->expects($this->once())->method('isValid')->willReturn(true);

        $this->checkSuccessfulPostResponse(
            [CaptchaResponseResolverInterface::PARAM_RECAPTCHA => 'test']
        );
    }

    /**
     * Given recaptcha parameter is missing in the request
     * And recaptcha is required
     * When no validation is able to take place due to the parameter being missing
     * Then assert general technical error is raised in session
     *
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testPostRequestIfReCaptchaParameterIsMissing(): void
    {
        $this->setConfig(true, 'test_public_key', 'test_private_key');

        $this->checkFailedPostResponse(
            [],
            ['Something went wrong with reCAPTCHA. Please contact the store owner.']
        );
    }

    /**
     * Given recaptcha parameter is incorrect in the request
     * And recaptcha is required
     * When validation fails but there are no validation errors supplied
     * Then assert general technical error specified in config is raised in session
     *
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testPostRequestWithFailedReCaptchaValidationWithGeneralTechnicalError(): void
    {
        $this->setConfig(true, 'test_public_key', 'test_private_key');
        $this->recaptchaValidationResultMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->recaptchaValidationResultMock->expects($this->once())->method('getErrors')->willReturn([]);

        $this->checkFailedPostResponse(
            [CaptchaResponseResolverInterface::PARAM_RECAPTCHA => 'test'],
            ['Something went wrong with reCAPTCHA. Please contact the store owner.']
        );
    }

    /**
     * Given recaptcha parameter is incorrect in the request
     * And recaptcha is required
     * When validation fails and there is a validation error supplied
     * Then assert general validation error specified in config is raised in session
     *
     * @magentoConfigFixture default_store company/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/company_create invisible
     */
    public function testPostRequestWithFailedReCaptchaValidationWithGeneralValidationError(): void
    {
        $this->setConfig(true, 'test_public_key', 'test_private_key');
        $this->recaptchaValidationResultMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->recaptchaValidationResultMock->expects($this->once())->method('getErrors')->willReturn([
            'score-threshold-not-met' => 'Score threshold not met.'
        ]);

        $this->checkFailedPostResponse(
            [CaptchaResponseResolverInterface::PARAM_RECAPTCHA => 'test'],
            ['reCAPTCHA verification failed.']
        );
    }

    /**
     * @param bool $shouldContainReCaptcha
     * @return void
     */
    private function checkSuccessfulGetResponse($shouldContainReCaptcha = false): void
    {
        $this->dispatch('company/account/create');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);

        $shouldContainReCaptcha
            ? $this->assertStringContainsString('field-recaptcha', $content)
            : $this->assertStringNotContainsString('field-recaptcha', $content);

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function checkSuccessfulPostResponse(array $postValues = []): void
    {
        $this->makePostRequest($postValues);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('company/account/index')));
        $customer = $this->customerRepository->get('john.doetsg@example.com');
        $companyId = $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
        self::assertNotNull($customer->getId());
        $company = $this->companyRepository->get($companyId);
        self::assertNotNull($company->getId());
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * @param array $postValues
     * @param array $expectedSessonMessages
     * @return void
     */
    private function checkFailedPostResponse(array $postValues = [], array $expectedSessonMessages = []): void
    {
        $this->makePostRequest($postValues);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('company/account/create')));
        try {
            // Ensure company creation failed; verify the company customer is absent in database
            $this->customerRepository->get('john.doetsg@example.com');
            self::fail('Company creation was successful; company should not have been created');
        } catch (NoSuchEntityException $e) {
            // Company is missing in database; this is expected behavior for a failed POST response
        }
        $this->assertSessionMessages(
            self::equalTo($expectedSessonMessages),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function makePostRequest(array $postValues = []): void
    {
        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(
                array_merge_recursive(
                    [
                        'company' => [
                            'company_name' => 'TSG',
                            'legal_name' => 'TSG Company',
                            'company_email' => 'tsg@example.com',
                            'country_id' => 'UA',
                            'region' => 'Kyiv region',
                            'city' => 'Kyiv',
                            'street' => [
                                0 => 'Somewhere',
                            ],
                            'postcode' => '01001',
                            'telephone' => '+1255555555',
                            'job_title' => 'Owner',
                        ],
                        'customer' => [
                            'firstname' => 'John',
                            'lastname' => 'Doe',
                            'email' => 'john.doetsg@example.com',
                        ],
                    ],
                    $postValues
                )
            );

        $this->dispatch('company/account/createpost');
    }

    /**
     * @param bool $isEnabled
     * @param string|null $publicRecaptchaKey
     * @param string|null $privateRecaptchaKey
     * @return void
     */
    private function setConfig(bool $isEnabled, ?string $publicRecaptchaKey, ?string $privateRecaptchaKey): void
    {
        $this->mutableScopeConfig->setValue(
            'btob/website_configuration/company_active',
            true,
            ScopeInterface::SCOPE_STORE
        );

        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_for/company_create',
            $isEnabled ? 'invisible' : null,
            ScopeInterface::SCOPE_WEBSITE
        );
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/public_key',
            $publicRecaptchaKey,
            ScopeInterface::SCOPE_WEBSITE
        );
        $this->mutableScopeConfig->setValue(
            'recaptcha_frontend/type_invisible/private_key',
            $privateRecaptchaKey,
            ScopeInterface::SCOPE_WEBSITE
        );
    }
}
