<?php namespace Rebing\GraphQL;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GraphQLController extends Controller {
    
    public function query(Request $request)
    {
        $query = $request->get('query');
        // If no 'params' given, check for 'variables'
        $params = $request->get(config('graphql.params_key'));
        
        if(is_string($params))
        {
            $params = json_decode($params, true);
        }
        
        return app('graphql')->query($query, $params);
    }
    
}
