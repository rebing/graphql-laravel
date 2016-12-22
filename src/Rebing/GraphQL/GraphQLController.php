<?php namespace Rebing\GraphQL;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GraphQLController extends Controller {

    public function query(Request $request)
    {
        // If a singular query was not found, it means the queries are in batch
        $batch = $request->get('query') ? [$request->all()] : $request->all();

        $completedQueries = [];
        $paramsKey = config('graphql.params_key');

        // Complete each query in order
        foreach($batch as $batchItem)
        {
            $query = $batchItem['query'];
            $params = array_get($batchItem, $paramsKey);

            if(is_string($params))
            {
                $params = json_decode($params, true);
            }

            $completedQueries[] = app('graphql')->query($query, $params);
        }

        return $completedQueries;
    }

}
