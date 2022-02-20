<?php

namespace App\Services;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * OpenGraph Generator Service
 *
 * This class implements functionality for processing HTML and parsing it to generate opengraph
 *
 */
class OpenGraphGenerator
{
    /**
     * @array - request values
     */
    private $parameters;

    /**
     * @string - Original HTML
     */
    private $html;

    /**
     * @array - OpenGraph - Processed metatags array
     */
    private $openGraphArr;

    /**
     * @array - Open Graph values already found
     */
    private $confirmedOG;

    function __construct($html, $openGraphArr, $confirmedOG, $parameters){
        $this->html = $html;
        $this->openGraphArr = $openGraphArr;
        $this->confirmedOG = $confirmedOG;
        $this->parameters = $parameters;
    }

    /**
     * Check if defined OG(from config-opengraph) are missing and fetches ones that are
     *
     * @return array
     */
    function check(){
        $uniqueOG = $this->confirmedOG->unique();
        $missingOG = config('opengraph.properties')->diff($uniqueOG);
        if(!$missingOG->isEmpty()){
            foreach($missingOG as $og){
                switch($og){
                    case 'og:title':
                        $this->generateTitle();
                        break;
                    case 'og:url':
                        $this->generateURL();
                        break;
                    case 'og:image':
                        $this->generateImage();
                        break;
                    case 'og:type':
                        $this->generateType();
                        break;
                }
            }
        }

        return $this->openGraphArr;
    }

    /**
     * Generate og:title from HTML, sets openGraphArr
     *
     * @return void
     */
    function generateTitle(){
        $crawler = new Crawler($this->html);
        $title = $crawler->filter('title')->first()->text();

        if(empty($title)){
            $allTitles = array(
                $this->elementCrawler($crawler, 'h1'),
                $this->elementCrawler($crawler, 'h2'),
                $this->elementCrawler($crawler, 'h3'),
                $this->elementCrawler($crawler, 'h4'),
                $this->elementCrawler($crawler, 'h5'),
                $this->elementCrawler($crawler, 'h6')
            );
            // Get first non - empty
            $megaTitle =  current(array_filter($allTitles));

            if(!empty($megaTitle) ){
                $this->openGraphArr[] = array(
                    'property' => 'og:title',
                    'content' => $megaTitle
                );
            }
        }else{
            $this->openGraphArr[] = array(
                'property' => 'og:title',
                'content' => $title
            );
        }
    }

    /**
     * Process HTML to find requested element, return text of element
     *
     * @param $crawler object, $element string
     *
     * Can be extended to run through different elements to make sure text exists.
     * Can be extended to fetch text from data attributes
     *
     * @return string
     */
    private function elementCrawler($crawler, $element, $source = 'text'){
        $val = null;
        if ($crawler->filter($element)->count() > 0){
            $val = $crawler->filter($element)->first()->$source();
        }
        return $val;
    }

    /**
     * Generate og:url from url parameter, sets openGraphArr
     *
     * @return void
     */
    function generateURL(){
        $this->openGraphArr[] = array(
            'property' => 'og:url',
            'content' => $this->parameters['url']
        );
    }

    /**
     * Generate og:image from HTML, sets openGraphArr
     * Can be extended to analyze images
     *
     * @return void
     */
    function generateImage(){
        $crawler = new Crawler($this->html);
        if($crawler->filterXPath('//img')->count() > 0){
            $imageLink = $crawler->filterXPath('//img')->first()->attr('src');
            if(!empty($imageLink) ){
                $this->openGraphArr[] = array(
                    'property' => 'og:image',
                    'content' => $imageLink
                );
            }
        }
    }

    /**
     * Generate og:type, sets openGraphArr
     *
     * @return void
     */
    function generateType(){
        $this->openGraphArr[] = array(
            'property' => 'og:type',
            'content' => 'website'
        );
    }

}
