<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/**
 * Class ApiController.
 */
class ApiController extends Controller
{
    /**
     * Default status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Default headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * @param mixed $data
     *
     * @return JsonResponse
     */
    public function respondCreated($data = '')
    {
        return $this->setStatusCode(201)->respond($data);
    }

    /**
     * @param mixed $data
     *
     * @return JsonResponse
     */
    public function respond($data = '')
    {
        return response()->json($data, $this->getStatusCode(), $this->headers);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondPaginator($data = '')
    {
        return response($data, $this->getStatusCode(), $this->headers);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondDeleted($data = 'Deleted.')
    {
        return response()->json($data, 204);
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondUnauthorized($data = 'Unauthorized.')
    {
        return $this->setStatusCode(401)->respondWithError($data);
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondBadRequest($data = 'Bad request.')
    {
        return $this->setStatusCode(400)->respondWithError($data);
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondWithError($data = 'Error')
    {
        return $this->respond([
            'error' => [
                'message'     => $data,
                'status_code' => $this->getStatusCode(),
            ],
        ]);
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondForbidden($data = 'Forbidden.')
    {
        return $this->setStatusCode(403)->respondWithError($data);
    }

    /**
     * @param string $data
     *
     * @return JsonResponse
     */
    public function respondNotFound($data = 'Not Found.')
    {
        return $this->setStatusCode(404)->respondWithError($data);
    }
}
