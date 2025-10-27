<?php
require __DIR__ . "/vendor/autoload.php";

use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

$router = new \Bramus\Router\Router();


header("content-type: application/json");

$router->get("/", function () {
    $bing = new \Wahidin\Feed\Bing;
    $query = $_GET["q"] ?? "viral news"; // Default
    try {
        $res = $bing->get($query);
    } catch (\Throwable $th) {
        http_response_code(500);
        $res = [
            "error" => true,
            "message" => $th->getMessage()
        ];
    } finally {
        echo json_encode($res, JSON_PRETTY_PRINT);
    }
});


$router->get("/view/{url}", function ($url) {
    $decodedUrl = base64_decode($url);
    $readability = new Readability(new Configuration());
    $html = file_get_contents($decodedUrl);
    try {
        $readability->parse($html);
        $res = [
            "title" => $readability->getTitle(),
            "content" => $readability->getContent(),
            // "excerpt" => $readability->getExcerpt(),
            // "image" => $readability->getImage(),
            // "images" => $readability->getImages(),
            // "author" => $readability->getAuthor(),
        ];
    } catch (ParseException $e) {
        http_response_code(500);
        $res = [
            "error" => true,
            "message" => $e->getMessage()
        ];
    } finally {
        echo json_encode($res, JSON_PRETTY_PRINT);
    }
});



$router->run();
