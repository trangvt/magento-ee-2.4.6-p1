<?php

declare(strict_types=1);

namespace Laminas\Filter\File;

use Laminas\Filter;
use Laminas\Filter\Exception;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_scalar;
use function is_writable;

/**
 * Encrypts a given file and stores the encrypted file content
 *
 * @deprecated Since 2.24.0. This filter will be removed in 3.0. You are encouraged to use an alternative encryption
 *             library and write your own filter.
 *
 * @psalm-suppress DeprecatedInterface
 */
class Encrypt extends Filter\Encrypt
{
    /**
     * New filename to set
     *
     * @var string
     */
    protected $filename;

    /**
     * Returns the new filename where the content will be stored
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Sets the new filename where the content will be stored
     *
     * @param  string $filename (Optional) New filename to set
     * @return self
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Defined by Laminas\Filter\Filter
     *
     * Encrypts the file $value with the defined settings
     *
     * @param  string|array $value Full path of file to change or $_FILES data array
     * @return string|array The filename which has been set, or false when there were errors
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function filter($value)
    {
        if (! is_scalar($value) && ! is_array($value)) {
            return $value;
        }

        // An uploaded file? Retrieve the 'tmp_name'
        $isFileUpload = false;
        if (is_array($value)) {
            if (! isset($value['tmp_name'])) {
                return $value;
            }

            $isFileUpload = true;
            $uploadData   = $value;
            $value        = $value['tmp_name'];
        }

        if (! file_exists($value)) {
            throw new Exception\InvalidArgumentException("File '$value' not found");
        }

        if (! isset($this->filename)) {
            $this->filename = $value;
        }

        if (file_exists($this->filename) && ! is_writable($this->filename)) {
            throw new Exception\RuntimeException("File '{$this->filename}' is not writable");
        }

        $content = file_get_contents($value);
        if (! $content) {
            throw new Exception\RuntimeException("Problem while reading file '$value'");
        }

        $encrypted = parent::filter($content);
        $result    = file_put_contents($this->filename, $encrypted);

        if (! $result) {
            throw new Exception\RuntimeException("Problem while writing file '{$this->filename}'");
        }

        if ($isFileUpload) {
            $uploadData['tmp_name'] = $this->filename;
            return $uploadData;
        }
        return $this->filename;
    }
}
