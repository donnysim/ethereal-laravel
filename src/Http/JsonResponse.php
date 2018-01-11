<?php

namespace Ethereal\Http;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;
use UnexpectedValueException;

class JsonResponse extends BaseJsonResponse
{
    use ResponseTrait;

    /**
     * Response data.
     *
     * @var array
     */
    protected $data;

    /**
     * Add debug information to exceptions.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Response error.
     *
     * @var \Exception|\Illuminate\Validation\ValidationException|string|null
     */
    protected $error;

    /**
     * Response error message.
     *
     * @var string|null
     */
    protected $errorMessage;

    /**
     * Error type.
     *
     * @var string|null
     */
    protected $errorType;

    /**
     * Additional json fields.
     *
     * @var array
     */
    protected $fields;

    /**
     * Response message.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Response meta data.
     *
     * @var mixed
     */
    protected $meta;

    /**
     * Create a new json response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public static function make($data = null, $status = 200, array $headers = []): JsonResponse
    {
        $instance = new static(null, $status, $headers);

        return $instance->setContent($data);
    }

    /**
     * Add field to response.
     *
     * @param string|array $key
     * @param mixed $value This value will be used only if key is a string.
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \UnexpectedValueException
     */
    public function add($key, $value = null): JsonResponse
    {
        if (!$this->fields) {
            $this->fields = [];
        }

        if (\is_string($key)) {
            $this->fields[$key] = $value;
        } else {
            $this->fields = \array_merge($this->fields, $key);
        }

        return $this;
    }

    /**
     * Set response status code.
     *
     * @param int $code
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \InvalidArgumentException
     */
    public function code($code): JsonResponse
    {
        $this->setStatusCode($code);

        return $this;
    }

    /**
     * Set response data.
     *
     * @param mixed $data
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \UnexpectedValueException
     */
    public function data($data): JsonResponse
    {
        if (!$this->data) {
            $this->data = [];
        }

        if (\is_array($data)) {
            $this->setContent(\array_merge($this->data, $data));
        } elseif ($data instanceof Arrayable) {
            $this->setContent(\array_merge($this->data, $data->toArray()));
        }

        return $this;
    }

    /**
     * Add debugging information to exception response.
     *
     * @param bool $value
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public function debug($value = true): JsonResponse
    {
        $this->debug = $value;

        return $this;
    }

    /**
     * Response error.
     *
     * @param \Exception|\Illuminate\Validation\Validator|\Illuminate\Contracts\Support\MessageBag|string $error
     * @param string|null $message
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public function error($error, $message = null): JsonResponse
    {
        $this->error = $error;
        $this->errorMessage = $message;

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
    public function getContent(): string
    {
        if ($this->isInformational() || $this->isEmpty()) {
            return '';
        }

        $content = \json_encode($this->getResponseData(), $this->encodingOptions);

        if ($content === false || \json_last_error() !== \JSON_ERROR_NONE) {
            throw new UnexpectedValueException('Could not convert data to json.');
        }

        if ($this->callback !== null) {
            return \sprintf('/**/%s(%s);', $this->callback, $content);
        }

        return $content;
    }

    /**
     * Get structured response data.
     *
     * @return array
     */
    public function getResponseData(): array
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

        if (!empty($this->meta)) {
            $responseData['meta'] = $this->meta;
        }

        $responseData += ($this->fields ?: []);

        return $responseData;
    }

    /**
     * Set json response message.
     *
     * @param string $message
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public function message($message): JsonResponse
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Add meta data to response.
     *
     * @param string|array $key
     * @param mixed $value This value will be used only if key is a string.
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \UnexpectedValueException
     */
    public function meta($key, $value = null): JsonResponse
    {
        if (!$this->meta) {
            $this->meta = [];
        }

        if (\is_string($key)) {
            Arr::set($this->meta, $key, $value);
        } else {
            foreach ($key as $metaKey => $metaValue) {
                $this->meta[$metaKey] = $metaValue;
            }
        }

        return $this;
    }

    /**
     * Sends content for the current web response.
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \UnexpectedValueException
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function sendContent(): JsonResponse
    {
        echo $this->getContent();

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
        } elseif (!$this->headers->has('Content-Type') || $this->headers->get('Content-Type') === 'text/javascript') {
            // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
            // in order to not overwrite a custom definition.
            $this->headers->set('Content-Type', 'application/json');
        } else {
            $this->headers->set('Content-Type', 'application/json');
        }

        return parent::sendHeaders();
    }

    /**
     * Sets the response content.
     *
     * @param mixed $content
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public function setContent($content): JsonResponse
    {
        $this->data = $content;

        return $this;
    }

    /**
     * Set response data.
     * Valid types are array and objects that implement Arrayable.
     *
     * @param array|mixed $data Content that can be cast to array.
     *
     * @return \Ethereal\Http\JsonResponse
     * @throws \UnexpectedValueException
     */
    public function setData($data = []): JsonResponse
    {
        $this->setContent($data);

        return $this;
    }

    /**
     * Set response meta data.
     *
     * @param mixed $data
     *
     * @return \Ethereal\Http\JsonResponse
     */
    public function setMeta($data): JsonResponse
    {
        $this->meta = $data;

        return $this;
    }

    /**
     * Get error as data.
     *
     * @return mixed
     */
    protected function getErrorData()
    {
        $error = [
            'message' => $this->getErrorMessage(),
        ];

        if ($this->debug && \is_object($this->error)) {
            $error['exception'] = \get_class($this->error);
            $error['file'] = $this->error->getFile();
            $error['line'] = $this->error->getLine();
            $error['trace'] = \collect($this->error->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all();
        }

        if ($this->error instanceof ValidationException && $this->error->validator) {
            if ($this->error->validator) {
                $error['errors'] = $this->error->validator->errors();
            } else {
                $error['errors'] = [];
            }
        }

        return $error;
    }

    /**
     * Get exception message.
     *
     * @return string|null
     */
    protected function getErrorMessage()
    {
        if ($this->errorMessage) {
            return $this->errorMessage;
        }

        if (\is_string($this->error)) {
            return $this->error;
        }

        if (\is_object($this->error) && \method_exists($this->error, 'getMessage')) {
            return $this->error->getMessage();
        }

        return null;
    }
}
