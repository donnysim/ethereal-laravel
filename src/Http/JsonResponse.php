<?php

namespace Ethereal\Http;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\ResponseTrait;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

class JsonResponse extends Response
{
    use ResponseTrait;

    /**
     * Response data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * JSONP callback.
     *
     * @var string
     */
    protected $callback;

    // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
    // 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    protected $encodingOptions = 0;

    /**
     * Response message.
     *
     * @var null|string
     */
    protected $message;

    /**
     * Attach custom data to the response.
     *
     * @var array
     */
    protected $attach = [];

    /**
     * Response error.
     *
     * @var Exception|\Illuminate\Validation\ValidationException|string
     */
    protected $error;

    /**
     * Error code.
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Debug mode - shows original exception messages.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Constructor.
     *
     * @param mixed $data The response data
     * @param int $status The response status code
     * @param array $headers An array of response headers
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($data = null, $status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);

        $this->setData($data);
    }

    /**
     * Create a new json response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function make($data = null, $status = 200, $headers = [])
    {
        return new static($data, $status, $headers);
    }

    /**
     * Set response data.
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        if ($data === null) {
            $data = new \ArrayObject();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Get response data.
     *
     * @return array|mixed
     */
    public function getData()
    {
        if ($this->data instanceof Arrayable) {
            return $this->data->toArray();
        } elseif ($this->data instanceof JsonSerializable) {
            return $this->data->jsonSerialize();
        }

        return $this->data;
    }

    /**
     * Response error.
     *
     * @param \Exception|\Illuminate\Validation\Validator|\Illuminate\Contracts\Support\MessageBag|string $error
     * @param int|null $code
     * @return $this
     */
    public function error($error, $code = null)
    {
        $this->error = $error;
        $this->errorCode = $code;

        return $this;
    }

    /**
     * Enable or disable debug mode.
     *
     * @param boolean $value
     * @return $this
     */
    public function debug($value)
    {
        $this->debug = $value;

        return $this;
    }

    /**
     * Attach data to root of the response.
     *
     * @param array $data
     * @param bool $overwrite
     * @return $this
     */
    public function attach(array $data, $overwrite = false)
    {
        if ($overwrite) {
            $this->attach = $data;
        } else {
            $this->attach = array_merge($this->attach, $data);
        }

        return $this;
    }

    /**
     * Attach data to response.
     *
     * @param array $data
     * @return $this
     */
    public function attachData(array $data)
    {
        $this->attach = array_merge_recursive($this->attach, [
            'data' => $data,
        ]);

        return $this;
    }

    /**
     * Set json response message.
     *
     * @param string $message
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get structured response data.
     */
    public function getResponseData()
    {
        $responseData = [
            'success' => $this->isSuccessful(),
        ];

        if ($this->error && (! $this->isSuccessful())) {
            $responseData['error'] = $this->getErrorData();
        }

        if ($this->message) {
            $responseData['message'] = $this->message;
        }

        if (count($this->attach) > 0) {
            $responseData = array_merge_recursive($responseData, $this->attach);
        }

        return $responseData;
    }

    /**
     * Sends content for the current web response.
     *
     * @return Response
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function sendContent()
    {
        echo $this->getContent();

        return $this;
    }

    /**
     * Get data as string.
     *
     * @return string
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function getContent()
    {
        try {
            $content = json_encode($this->getResponseData(), $this->encodingOptions);
        } catch (\Exception $e) {
            if ('Exception' === get_class($e) && 0 === strpos($e->getMessage(), 'Failed calling ')) {
                throw $e->getPrevious() ?: $e;
            }
            throw $e;
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        if ($this->callback !== null) {
            return sprintf('/**/%s(%s);', $this->callback, $content);
        }

        return $content;
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        if ($this->callback !== null) {
            // Not using application/javascript for compatibility reasons with older browsers.
            $this->headers->set('Content-Type', 'text/javascript');
        }

        // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
        // in order to not overwrite a custom definition.
        elseif (! $this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/json');
        }

        return parent::sendHeaders();
    }

    /**
     * Sets the JSONP callback.
     *
     * @param string|null $callback The JSONP callback or null to use none
     * @return JsonResponse
     * @throws \InvalidArgumentException When the callback name is not valid
     */
    public function callback($callback = null)
    {
        if ($callback !== null) {
            // taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
            $parts = explode('.', $callback);
            foreach ($parts as $part) {
                if (! preg_match($pattern, $part)) {
                    throw new \InvalidArgumentException('The callback name is not valid.');
                }
            }
        }

        $this->callback = $callback;

        return $this;
    }

    /**
     * Returns options used while encoding data to JSON.
     *
     * @return int
     */
    public function encodingOptions()
    {
        return $this->encodingOptions;
    }

    /**
     * Sets options used while encoding data to JSON.
     *
     * @param int $encodingOptions
     * @return JsonResponse
     */
    public function setEncodingOptionsOrig($encodingOptions)
    {
        $this->encodingOptions = (int) $encodingOptions;

        return $this;
    }

    /**
     * Get error as data.
     *
     * @return array
     */
    protected function getErrorData()
    {
        $error = [
            'message' => $this->error,
            'code' => $this->getErrorCode(),
        ];

        if ($this->debug && $this->error instanceof \Exception) {
            $error['original'] = $this->error->getMessage() . ' at ' . substr($this->error->getFile(), strlen(base_path())) . ' line ' . $this->error->getLine();
        }

        if ($this->error instanceof ValidationException) {
            $error['message'] = $this->getErrorMessage($this->error);

            if ($this->error->validator) {
                $error['fields'] = static::flattenMessageBag($this->error->validator->messages());
            }
        } elseif ($this->error instanceof \Exception) {
            $error['message'] = $this->getErrorMessage($this->error);
        } elseif ($this->error instanceof Validator) {
            if ($this->error->fails()) {
                $error['message'] = 'failed'; //$this->getErrorMessage(ExceptionCodes::DATA_VALIDATION_FAILED);
                $error['fields'] = static::flattenMessageBag($this->error->messages());
            }
        } elseif ($this->error instanceof MessageBag) {
            $error['message'] = 'failed'; //$this->getErrorMessage(ExceptionCodes::DATA_VALIDATION_FAILED);
            $error['fields'] = static::flattenMessageBag($this->error);
        }

        return $error;
    }

    /**
     * Return error code.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return 1;

//        if (! $this->error) {
//            return $this->errorCode ?: ExceptionCodes::UNKNOWN_EXCEPTION;
//        } elseif ($this->errorCode) {
//            return $this->errorCode;
//        }
//
//        if ($this->error instanceof \Exception) {
//            return ExceptionCodes::getCode($this->error);
//        } else if ($this->error instanceof Validator || $this->error instanceof MessageBag) {
//            return ExceptionCodes::DATA_VALIDATION_FAILED;
//        }
//
//        return $this->errorCode ?: ExceptionCodes::UNKNOWN_EXCEPTION;
    }

    /**
     * Get exception message.
     *
     * @param \Exception|int|string $error
     * @return mixed
     */
    protected function getErrorMessage($error)
    {
        if (is_string($error)) {
            return $error;
        }

//        if (! $this->translateException || (! $this->translateException && is_int($error))) {
//            return ExceptionCodes::getTranslation($error);
//        }

        return $error->getMessage();
    }

    /**
     * Flatten validator messages.
     *
     * @param MessageBag|array $messages
     * @return array
     */
    public static function flattenMessageBag($messages)
    {
        $flattened = [];

        if ($messages instanceof MessageBag) {
            $messages = $messages->getMessages();
        }

        foreach ($messages as $key => $value) {
            if (is_array($value)) {
                $flattened[$key] = head($value);
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Check if data is paginated.
     *
     * @param $data
     * @return bool
     */
    public static function isPaginated($data)
    {
        return $data !== null && (($data instanceof Arrayable && $data instanceof AbstractPaginator) || (is_array($data) && isset($data['current_page'])));
    }

    /**
     * Get pagination data without details.
     *
     * @param $data
     * @return mixed
     */
    public static function getPaginationData($data)
    {
        if ($data instanceof Arrayable && $data instanceof AbstractPaginator) {
            $pagination = $data->toArray();

            return $pagination['data'];
        }

        return $data['data'];
    }

    /**
     * Get pagination details without data.
     *
     * @param array|\Illuminate\Contracts\Support\Arrayable|\Illuminate\Pagination\AbstractPaginator $data
     * @return array
     */
    public static function getPagination($data)
    {
        if ($data instanceof Arrayable && $data instanceof AbstractPaginator) {
            $pagination = $data->toArray();

            return Arr::except($pagination, 'data');
        }

        return Arr::except($data, 'data');
    }

}