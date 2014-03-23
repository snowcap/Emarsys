<?php

namespace Snowcap\Emarsys;


use Snowcap\Emarsys\Exception\ClientException;

class Response
{
    const REPLY_CODE_OK = 0;
    const REPLY_CODE_INTERNAL_ERROR = 1;
    const REPLY_CODE_INVALID_STATUS = 6003;
    const REPLY_CODE_INVALID_DATA = 10001;

    /**
     * @var int
     */
    protected $replyCode;
    /**
     * @var string
     */
    protected $replyText;
    /**
     * @var array
     */
    protected $data = array();

    /**
     * @param array $result
     * @throws Exception\ClientException
     */
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

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getReplyCode()
    {
        return $this->replyCode;
    }

    /**
     * @return string
     */
    public function getReplyText()
    {
        return $this->replyText;
    }
}