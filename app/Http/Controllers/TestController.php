<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class TestController extends Controller
{
    //Try out DOM crawler
    function parseElements(){
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8" test best>
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="robots" content="index, follow, all">
                <meta name="Author" content="Symfony">
                <meta property="og:title" content="dennisdup - Overview" />
                <meta http-equiv="x-pjax-js-version" content="9cd8fc961b1ca9cfaa8d56826564c0e5072140a6c1961abc9fb29d69da874532" data-turbo-track="reload">
            </head>
            <body>
                <meta name="theme-color" content="#262626">
                <p class="message">Hello World!</p>
                <p>Hello Crawler!</p>
            </body>
        </html>
        HTML;

        $crawler = new Crawler($html);

        $metaParsed = $crawler->filter('meta')->each( function($node, $i) {
                $meta = $node->outerHtml();
                $dom = new \DOMDocument;
                libxml_use_internal_errors(true);
                $dom->loadHTML($meta);

                $result = [];

                foreach($dom->getElementsByTagName('meta')->item(0)->attributes as $attr) {
                    $result[$attr->name] = $attr->value;
                }

                return $result;
        });

        echo json_encode($metaParsed);

    }
}
