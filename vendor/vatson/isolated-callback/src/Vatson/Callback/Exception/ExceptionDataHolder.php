<?php

namespace Vatson\Callback\Exception;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/27/12
 */
class ExceptionDataHolder
{
    /**
     * @var string
     */
    protected $original_class;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int|mixed
     */
    protected $code;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var int
     */
    protected $line;

    /**
     * @param \Exception $original_exception
     */
    public function __construct(\Exception $original_exception)
    {
        $this->message = $original_exception->getMessage();
        $this->code = $original_exception->getCode();
        $this->file = $original_exception->getFile();
        $this->line = $original_exception->getLine();
        $this->original_class = get_class($original_exception);
    }

    /**
     * @return string
     */
    public function getOriginalClass()
    {
        return $this->original_class;
    }

    /**
     * @return int|mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
