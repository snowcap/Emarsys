<?php

namespace Snowcap\Emarsys;


use Snowcap\Emarsys\Exception\ClientException;

class Response
{
    /**
     * @var int
     */
    public $replyCode;
    /**
     * @var string
     */
    public $replyText;
    /**
     * @var array
     */
    public $data = array();

    function __construct(array $result = array())
    {
        if (count($result) > 0) {
            if (!isset($result['replyCode']) || !isset($result['replyText']) || !isset($result['data'])) {
                throw new ClientException('Invalid result structure');
            }
            $this->replyCode = $result['replyCode'];
            $this->replyText = $result['replyText'];
            $this->data = $result['data'];
        }
    }


} 