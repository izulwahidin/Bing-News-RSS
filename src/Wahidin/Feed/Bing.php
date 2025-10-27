<?php

namespace Wahidin\Feed;

use Symfony\Component\DomCrawler\Crawler;

class Bing
{
    private $q = [];
    private $is_page;
    private $base_url = "https://www.bing.com/news/search?";
    private $body;
    private $feeds;



    private function fetch()
    {
        $res = file_get_contents($this->base_url . http_build_query($this->q));
        $this->body = $res;
        $this->is_page = (str_contains($res, "text/javascript")) ? true : false;

        if ($this->is_page) {
            $this->handlePage();
            return;
        }
        $this->handleFeed();
        return;
    }

    public function get($query = null)
    {
        $this->q["format"] = "rss";
        if ($query !== null) $this->q["q"] = $query;
        $this->fetch();
        return $this->feeds;
    }

    private function handlePage()
    {
        $news = new Crawler($this->body);
        $this->feeds = $news->filter(".news_fbcard.wimg")->each(function (Crawler $node) {
            $image = $node->filter(".citm_img img")->count() > 0 ? $node->filter(".citm_img img")->attr("data-src-hq") : $node->filter(".citm_img .rms_iac")->attr("data-src");
            parse_str(explode("th?", $image)[1], $imgData);

            return [
                "title" => $node->filter(".news_title")->text(""),
                "description" => $node->filter(".news_snpt")->text(""),
                "url" => $node->attr("href"),
                "full_content" => "https://fidesmedia.h3h3.eu.org/view/" . base64_encode($node->attr("href")),
                "image" =>  "https://bing.com/th?id=" . $imgData["id"],
                "source" => $node->filter(".na_footer_name")->text(""),
            ];
        });
    }
    private function handleFeed()
    {

        $rss = \Feed::loadRss($this->base_url . http_build_query($this->q));

        $res = [];
        foreach ($rss->item as $item) {
            $d = json_decode(json_encode($item));
            if (isset($d->{"News:Image"}) && str_contains($d->{"News:Image"}, "&")) {
                $image = explode("&", $d->{"News:Image"})[0];
            } else {
                $image = $d->{"News:Image"} ?? null;
            }
            $res[] = [
                "title" => $d->title,
                "description" => $d->description,
                "url" => $d->link,
                "full_content" => "https://fidesmedia.h3h3.eu.org/view/" . base64_encode($d->link),
                "image" =>  $image ?? null,
                "source" => $d->{"News:Source"} ?? null,
            ];
        }
        $this->feeds =  $res;
    }
}
