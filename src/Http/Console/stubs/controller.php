<?php

namespace DummyNamespace;

use Ethereal\Http\JsonResponse;
use Ethereal\Support\Builders\QueryBuilder;
use Ethereal\Support\EtherealController;
use DummyRootNamespaceDummyModel;

class DummyClass extends EtherealController
{
    protected $model = DummyModel::class;

    /**
     * Get a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $self = $this;

        $this
            ->authorize('index')
            ->query($this->model, function (QueryBuilder $query) {
                $query
                    ->orderByRequest($this->request, 'order')
                    ->paginateAs('model');
            })
            ->json(function (JsonResponse $json) {
                $json->attachData([
                    'Model' => JsonResponse::getPaginationData($this['model']),
                    'Model_pagination' => JsonResponse::getPagination($this['model'])
                ]);
            });

        return $this->json;
    }

    public function create()
    {
        
    }

    /**
     * Authorize user action.
     *
     * @param string $action
     * @param mixed $target
     * @param array $params
     * @return $this
     */
    protected function authorize($action, $target = null, array $params = [])
    {
        // TODO: Implement authorize() method.
    }
}