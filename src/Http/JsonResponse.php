<?php

namespace Ethereal\Http;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\ResponseTrait;
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
     * Additional json fields.
     *
     * @var array
     */
    protected $fields;

    /**
     * Response meta data.
     *
     * @var mixed
     */
    protected $meta;

    /**
     * JSONP callback.
     *
     * @var string
     */
    protected $callback;

    /**
     * Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
     * 15 === JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
     *
     * @var int
     */
    protected $encodingOptions = 0;

    /**
     * Response message.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Response error.
     *
     * @var \Exception|\Illuminate\Validation\ValidationException|string|null
     */
    protected $error;

    /**
     * Error code.
     *
     * @var mixed
     */
    protected $errorCode;

    /**
     * Error type.
     *
     * @var string|null
     */
    protected $errorType;

    /**
     * Create a new json response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     *
     * @return $this
     */
    public static function make($data = null, $status = 200, $headers = [])
    {
        $instance = new static(null, $status, $headers);

        return $instance->setContent($data);
    }

    /**
     * Sets the response content.
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->data = $content;

        return $this;
    }

    /**
     * Set response meta data.
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setMeta($data)
    {
        $this->meta = $data;

        return $this;
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
        elseif (!$this->headers->has('Content-Type') || $this->headers->get('Content-Type') === 'text/javascript') {
            $this->headers->set('Content-Type', 'application/json');
        } else {
            $this->headers->set('Content-Type', 'application/json');
        }

        return parent::sendHeaders();
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
     * @param mixed $code
     * @param string|null $type
     *
     * @return $this
     */
    public function error($error, $code = null, $type = null)
    {
        $this->error = $error;
        $this->errorCode = $code;
        $this->errorType = $type ?: class_basename($error);

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
            'data' => $this->data,
        ];

        if ($this->error && !$this->isSuccessful()) {
            $responseData['error'] = $this->getErrorData();
        }

        if ($this->message) {
            $responseData['message'] = $this->message;
        }

        $responseData += ($this->fields ?: []);

        return $responseData;
    }

    /**
     * Get error as data.
     *
     * @return mixed
     */
    protected function getErrorData()
    {
        $error = [
            'message' => $this->error,
            'type' => $this->getErrorType(),
            'code' => $this->getErrorCode(),
        ];

        if ($this->error instanceof ValidationException) {
            $error['message'] = $this->getErrorMessage();

            if ($this->error->validator) {
                $error['fields'] = static::flattenMessageBag($this->error->validator->messages());
            }
        } elseif ($this->error instanceof Exception) {
            $error['message'] = $this->getErrorMessage();
        } else {
            return $this->error;
        }

        return $error;
    }

    /**
     * Get exception type.
     *
     * @return string|null
     */
    protected function getErrorType()
    {
        if (is_object($this->error) && $this->error instanceof Validator || $this->error instanceof MessageBag) {
            return class_basename(ValidationException::class);
        }

        return $this->errorType;
    }

    /**
     * Get exception code.
     *
     * @return int
     */
    protected function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get exception message.
     *
     * @return string
     */
    protected function getErrorMessage()
    {
        if (is_string($this->error)) {
            return $this->error;
        }

        return $this->error->getMessage();
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
     * Add meta data to response.
     *
     * @param string|array $key
     * @param mixed $value This value will be used only if key is a string.
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function meta($key, $value = null)
    {
        if (!$this->meta) {
            $this->meta = [];
        }

        if (is_string($key)) {
            Arr::set($this->meta, $key, $value);
        } else {
            $this->meta = array_merge_recursive($this->meta, $key);
        }

        return $this;
    }

    /**
     * Add field to response.
     *
     * @param string|array $key
     * @param mixed $value This value will be used only if key is a string.
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function add($key, $value = null)
    {
        if (!$this->fields) {
            $this->fields = [];
        }

        if (is_string($key)) {
            $this->fields[$key] = $value;
        } else {
            $this->fields = array_merge($this->fields, $key);
        }

        return $this;
    }

    /**
     * Set response data.
     *
     * @param mixed $data
     *
     * @return $this
     * @throws \UnexpectedValueException
     */
    public function data($data)
    {
        $this->setContent($data);

        return $this;
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
