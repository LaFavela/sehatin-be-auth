<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpParser\Node\Expr\Cast\Object_;

class MessageResource extends JsonResource
{
    protected bool $status;
    protected string $message;
    protected ?string $error;

    public function __construct($resource, bool $status, string $message, string $error = null)
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
        $this->error = $error;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [];
        $response = array_merge($response, [
            /**
             * The response status.
             * @var boolean $status
             * @example true
             */
            'status' => $this->status,
            /**
             * The response message.
             * @var string $message
             * @example The data is successfully updated
             */
            'message' => $this->message,
        ]);

        if (isset($this->resource)) {
            // Check if the resource is an AnonymousResourceCollection
            if ($this->resource instanceof AnonymousResourceCollection) {
                $pagination = $this->resource->resource; // Get the paginator instance from the collection
            } else if ($this->resource instanceof LengthAwarePaginator) {
                $pagination = $this->resource;
            }

            if (isset($pagination) && $pagination instanceof LengthAwarePaginator) {
                $paginationData = $pagination->toArray();
                $resource = [
                    'data' => $paginationData['data'],
                    /**
                     * The response links.
                     * @var array $links
                     */
                    'links' => [
                        'first' => $paginationData['first_page_url'],
                        'last' => $paginationData['last_page_url'],
                        'prev' => $paginationData['prev_page_url'],
                        'next' => $paginationData['next_page_url'],
                    ],
                    /**
                     * The response meta.
                     * @var array $meta
                     */
                    'meta' => [
                        'current_page' => $paginationData['current_page'],
                        'from' => $paginationData['from'],
                        'last_page' => $paginationData['last_page'],
                        'links' => $paginationData['links'],
                        'path' => $paginationData['path'],
                        'per_page' => $paginationData['per_page'],
                        'to' => $paginationData['to'],
                        'total' => $paginationData['total'],
                    ]
                ];
                $response = array_merge($response, $resource);
            } else {
                $response = array_merge($response,
                    [
                        /**
                         * The response data.
                         * @var array $data
                         * @example [{"id":1,"name":"John Doe"},{"id":2,"name":"Jane Doe"}]
                         */
                        'data' => $this->resource
                    ]);
            }
        }

        if (isset($this->error)) {
            $errorJsonObject = json_decode($this->error, true);
            // if it's not json object then return as it is
            if ($errorJsonObject) {
                $errorArray = [];
                foreach ($errorJsonObject as $key => $value) {
                    $errorArray[] = "$key: [" . implode(', ', $value) . "]";
                }
                $formattedError = implode(', ', $errorArray);
            } else {
                $formattedError = $this->error;
            }
            $error = [
                /**
                 * The response error. It's NOT REQUIRED.
                 * @var string $error
                 * @example SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'test' for key 'partners_name_unique'
                 */
                'error' => $formattedError];
            $response = array_merge($response, $error);
        }
        return $response;
    }

    /**
     * Customize the response for a resource.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public
    function withResponse(Request $request, $response): void
    {
        $response->setData($this->toArray($request));
    }
}
