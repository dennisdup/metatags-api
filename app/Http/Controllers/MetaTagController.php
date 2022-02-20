<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MetaTag;
use App\Services\OpenGraphGenerator;
use Validator;

/**
 * MetaTag Class
 *
 * This class controls /metatag endpoint
 *
 */

class MetaTagController extends Controller
{

    public function __construct(){

    }

    /**
     * Get MetaTags
     *
     * @param string $url
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function fetchMetaTags(Request $request){
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $parameters = array('url' => $request->url, 'opengraph' => false);
        $metatag = new MetaTag($parameters);

        list($statusCode, $result) = $metatag->fetch();

        return response()->json([
            'results' => $result
        ], $statusCode);
    }

    /**
     * Get OpenGraph
     *
     * @param string $url
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function openGraph(Request $request){
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $parameters = array('url' => $request->url, 'opengraph' => true);
        $metatag = new MetaTag($parameters);

        list($statusCode, $result) = $metatag->fetch();

        $processed = $result;
        if($statusCode === 200){
            $ogGenerator = new OpenGraphGenerator($metatag->originalHTML, $result, $metatag->confirmedOG, $parameters);
            $processed = $ogGenerator->check();
        }

        return response()->json([
            'results' => $processed
        ], $statusCode);
    }

}
