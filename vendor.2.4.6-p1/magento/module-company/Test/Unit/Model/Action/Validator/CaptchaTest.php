<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Action\Validator;

use Magento\Captcha\Helper\Data;
use Magento\Captcha\Model\CaptchaInterface;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Company\Model\Action\Validator\Captcha;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helper;

    /**
     * @var CaptchaStringResolver|MockObject
     */
    private $stringResolver;

    /**
     * @var Captcha
     */
    private $captcha;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->stringResolver = $this->createMock(
            CaptchaStringResolver::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->captcha = $objectManagerHelper->getObject(
            Captcha::class,
            [
                'helper' => $this->helper,
                'stringResolver' => $this->stringResolver,
            ]
        );
    }

    /**
     * Test for validate method.
     *
     * @return void
     */
    public function testValidate()
    {
        $formId = 1;
        $captchaValue = '123Q';
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $captcha = $this->getMockForAbstractClass(
            CaptchaInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isRequired', 'isCorrect']
        );
        $this->helper->expects($this->once())->method('getCaptcha')->with($formId)->willReturn($captcha);
        $captcha->expects($this->once())->method('isRequired')->willReturn(true);
        $this->stringResolver->expects($this->once())
            ->method('resolve')->with($request, $formId)->willReturn($captchaValue);
        $captcha->expects($this->once())->method('isCorrect')->with($captchaValue)->willReturn(true);

        $this->assertTrue($this->captcha->validate($formId, $request));
    }
}
