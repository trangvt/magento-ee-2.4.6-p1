<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;

/**
 * Retrieve error type based on the exception class
 */
class GetErrorType
{
    private const NOT_FOUND = 'NOT_FOUND';
    private const OPERATION_NOT_APPLICABLE = 'OPERATION_NOT_APPLICABLE';
    private const COULD_NOT_SAVE = 'COULD_NOT_SAVE';
    private const NOT_VALID_DATA = 'NOT_VALID_DATA';
    private const UNDEFINED = 'UNDEFINED';

    /**
     * Retrieve error type based on the exception class
     *
     * @param Exception $exception
     * @return string
     */
    public function execute(Exception $exception): string
    {
        switch (get_class($exception)) {
            case NoSuchEntityException::class:
                return self::NOT_FOUND;
            case PurchaseOrderValidationException::class:
                return self::OPERATION_NOT_APPLICABLE;
            case CouldNotSaveException::class:
                return self::COULD_NOT_SAVE;
            case InputException::class:
                return self::NOT_VALID_DATA;
            default:
                return self::UNDEFINED;
        }
    }
}
