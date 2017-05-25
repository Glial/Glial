<?php

namespace Vatson\Callback\Exception;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallbackExecutionException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $original_class;

    /**
     * @param ExceptionDataHolder $exception_holder
     */
    public function __construct(ExceptionDataHolder $exception_holder)
    {
        $this->message = $exception_holder->getMessage();
        $this->code = $exception_holder->getCode();
        $this->file = $exception_holder->getFile();
        $this->line = $exception_holder->getLine();

        $this->original_class = $exception_holder->getOriginalClass();
    }

    /**
     * @return string
     */
    public function getOriginalClass()
    {
        return $this->original_class;
    }
}
