<?php

namespace Ethereal\Http;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\ResponseTrait;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class JsonResponse extends Response
{
    use ResponseTrait;

    /**
     * Response data.
     *
     * @var array
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
     * Response error.
     *
     * @var \Exception|\Illuminate\Validation\ValidationException|string
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
     * Create a new json response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public static function make($data = null, $status = 200, $headers = [])
    {
        $instance = new static(null, $status, $headers);

        return $instance->setContent($data);
    }

    /**
     * Get pagination data without details.
     *
     * @param $data
     *
     * @return array
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
     *
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

    /**
     * Check if data is paginated.
     *
     * @param $data
     *
     * @return bool
     */
    public static function isPaginated($data)
    {
        return $data !== null && (($data instanceof Arrayable && $data instanceof AbstractPaginator) || (is_array($data) && isset($data['current_page'])));
    }

    /**
     * Sends HTTP headers.
     *
     * @return \Ethereal\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function sendHeaders()
    {
        if ($this->callback !== null) {
            // Not using application/javascript for compatibility reasons with older browsers.
            $this->headers->set('Content-Type', 'text/javascript');
        }

        // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
        // in order to not overwrite a custom definition.
        elseif (!$this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/json');
        } else {
            $this->headers->set('Content-Type', 'application/json');
        }

        return parent::sendHeaders();
    }

    /**
     * Enable or disable debug mode.
     *
     * @param boolean $value
     *
     * @return $this
     */
    public function debug($value)
    {
        $this->debug = $value;

        return $this;
    }

    /**
     * Set json response message.
     *
     * @param string $message
     *
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Response error.
     *
     * @param \Exception|\Illuminate\Validation\Validator|\Illuminate\Contracts\Support\MessageBag|string $error
     * @param int|null $code
     *
     * @return $this
     */
    public function error($error, $code = null)
    {
        $this->error = $error;
        $this->errorCode = $code;

        return $this;
    }

    /**
     * Sends content for the current web response.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \UnexpectedValueException
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
     * @throws \UnexpectedValueException
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function getContent()
    {
        $content = json_encode($this->getResponseData(), $this->encodingOptions);

        if ($content === false || json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException('Could not convert data to json.');
        }

        if ($this->callback !== null) {
            return sprintf('/**/%s(%s);', $this->callback, $content);
        }

        return $content;
    }

    /**
     * Get structured response data.
     *
     * @return array
     */
    public function getResponseData()
    {
        $responseData = [
            'success' => $this->isSuccessful(),
        ];

        if ($this->error && (!$this->isSuccessful())) {
            $responseData['error'] = $this->getErrorData();
        }

        if ($this->message) {
            $responseData['message'] = $this->message;
        }

        $responseData = array_merge_recursive($responseData, $this->data);

        return $responseData;
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
            'code' => $this->errorCode,
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
                $error['message'] = 'failed';
                $error['fields'] = static::flattenMessageBag($this->error->messages());
            }
        } elseif ($this->error instanceof MessageBag) {
            $error['message'] = 'failed';
            $error['fields'] = static::flattenMessageBag($this->error);
        }

        return $error;
    }

    /**
     * Get exception message.
     *
     * @param \Exception|int|string $error
     *
     * @return string
     */
    protected function getErrorMessage($error)
    {
        if (is_string($error)) {
            return $error;
        }

        return $error->getMessage();
    }

    /**
     * Flatten validator messages.
     *
     * @param \Illuminate\Support\MessageBag|array $messages
     *
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
     * Set response data.
     * Valid types are array and objects that implement Arrayable.
     *
     * @param array|\Illuminate\Contracts\Support\Arrayable $data Content that can be cast to array.
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function setData($data)
    {
        $this->setContent($data);

        return $this;
    }

    /**
     * Sets the response content.
     * Valid types are array and objects that implement Arrayable.
     *
     * @param array|Arrayable $content Content that can be cast to array.
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function setContent($content)
    {
        if ($content === null) {
            $data = [];
        } elseif ($content instanceof Collection) {
            $data = $content->toArray();
        } else {
            $data = $content;
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Add payload data to response.
     *
     * @param string|array $key
     * @param mixed $value This value will be used only if key is a string.
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function payload($key, $value = null)
    {
        if (is_string($key)) {
            $this->data([
                'data' => [
                    $key => $value,
                ],
            ]);
        } else {
            $this->data([
                'data' => $key,
            ]);
        }

        return $this;
    }

    /**
     * Add data to response.
     * Valid types are array and objects that implement Arrayable.
     *
     * @param array|\Illuminate\Contracts\Support\Arrayable $data
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function data($data)
    {
        $this->setContent(array_merge_recursive($this->data, $data));

        return $this;
    }

    /**
     * Add payload data to response.
     *
     * @param array $payload
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function setPayload($payload)
    {
        $this->data['data'] = $payload;

        return $this;
    }

    /**
     * Get response payload.
     *
     * @return array|null
     */
    public function getPayload()
    {
        if (!isset($this->data['data'])) {
            return null;
        }

        return $this->data['data'];
    }

    /**
     * Sets the JSONP callback.
     *
     * @param string|null $callback The JSONP callback or null to use none
     *
     * @return $this
     * @throws \InvalidArgumentException When the callback name is not valid
     */
    public function callback($callback = null)
    {
        if ($callback !== null) {
            // taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
            $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
            $parts = explode('.', $callback);
            foreach ($parts as $part) {
                if (!preg_match($pattern, $part)) {
                    throw new InvalidArgumentException('The callback name is not valid.');
                }
            }
        }

        $this->callback = $callback;

        return $this;
    }

    /**
     * Set response status code.
     *
     * @param int $code
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function code($code)
    {
        $this->setStatusCode($code);

        return $this;
    }
}
