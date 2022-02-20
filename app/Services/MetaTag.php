<?php

namespace App\Services;

use Goutte\Client;
use \Illuminate\Support\Arr;

/**
 * MetaTag Service
 *
 * This class implements functionality for fetching scrapping HTML and parsing it to get metatags
 *
 */
class MetaTag
{
    /**
     * @array - request values
     */
    private $parameters;

    /**
     * @string
     */
    public $originalHTML;

    /**
     * @array - Open Graph values found
     */
    public $confirmedOG;

    function __construct($parameters){
        $this->parameters = $parameters;
        $this->confirmedOG = collect([]);
    }

    /**
     * Fetch HTML and process Metatags
     *
     * @return array
     */
    function fetch(){
        $client = new Client();

        // Fetch HTML
        try{
            $crawler = $client->request('GET', $this->parameters['url']);
            $this->originalHTML = $crawler->html();
        }catch(\Exception $e){
            report($e);

            return array(400, [
                'error' => $e->getMessage()
            ]);
        }

        // Parse MetaTags
        try{
            $metaParsed = $crawler->filter('meta')->each( function($node, $i) {

                            $meta = $node->outerHtml();
                            $metaArr = $this->processMetaTag($meta);
                            if($this->parameters['opengraph']){
                                if(isset($metaArr['property'])){
                                    $checker = $this->parseOg($metaArr['property']);

                                    if($checker){
                                        return $metaArr;
                                    };
                                }
                            }else{
                                return $metaArr;
                            }
                        });
        }catch(\Exception $e){
            report($e);

            return array(422, [
                'error' => $e->getMessage()
            ]);
        }

        return array( 200, array_values(Arr::whereNotNull($metaParsed)) );
    }

    /**
     * Process Metatag string to return an array of metatag attributes
     *
     * @param  string $meta
     *
     * @return array
     */
    protected function processMetaTag($meta){
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($meta);

        $result = [];

        foreach($dom->getElementsByTagName('meta')->item(0)->attributes as $attr) {
            $result[$attr->name] = $attr->value;
        }

        return $result;
    }

    /**
     * Process Metatag value to determine if it's an opengraph property
     *
     * @param  string $metavalue
     *
     * @return bool
     */
    protected function parseOg($value){
        $checker = false;

        if(empty($value) || !is_string($value)|| strlen($value) < 3 ){
            $checker = false;
        }

        if(substr($value, 0, 3) === 'og:'){
            $this->checkOG($value);
            $checker = true;
        }

        return $checker;
    }

    /**
     * Check if Defined OG value exist from existing metatags, set confirmedOG
     *
     * @param  string $metavalue
     *
     * @return void
     */
    protected function checkOG($value){
        $og = config('opengraph.properties');
        if( $og->contains($value) ){
            $this->confirmedOG->push($value);
        }
    }


}

?>
