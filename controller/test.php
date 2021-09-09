<?php
namespace jeb\snahp\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;
use jeb\snahp\core\invite_helper;

class test
{
    protected $db;
    protected $user;
    protected $auth;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $cache;
    protected $tbl;
    protected $sauth;

    public function __construct(
        $db,
        $user,
        $auth,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $cache,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->cache = $cache;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = (int) $this->user->data["user_id"];
    }

    public function handle($mode)
    {
        switch ($mode) {
            case "imgcompare":
                $cfg["tpl_name"] = "@jeb_snahp/imgcompare/base.html";
                $cfg["title"] = "Test";
                return $this->respond_imgcompare($cfg);
            case "purge_cache":
                $this->sauth->reject_non_dev();
                $this->config->increment("assets_version", 1);
                $this->cache->purge();
                return new JsonResponse([
                    "status" => "ready",
                    "job" => "purged cache",
                    "number" => mt_rand(0, 1000),
                ]);
            case "test_json":
                $strn =
                    '[{"id":"3462456","books_count":"24","ratings_count":"516769","text_reviews_count":"10855","original_publication_year":"1955","original_publication_month":"10","original_publication_day":"20","average_rating":"4.49","best_book":{"@attributes":{"type":"Book"},"id":"33","title":"The Lord of the Rings (The Lord of the Rings, #1-3)","author":{"id":"656983","name":"J.R.R. Tolkien"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1566425108l/33._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1566425108l/33._SX50_.jpg"}},{"id":"3204327","books_count":"583","ratings_count":"2165149","text_reviews_count":"19718","original_publication_year":"1954","original_publication_month":"7","original_publication_day":"29","average_rating":"4.36","best_book":{"@attributes":{"type":"Book"},"id":"34","title":"The Fellowship of the Ring (The Lord of the Rings, #1)","author":{"id":"656983","name":"J.R.R. Tolkien"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1298411339l/34._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1298411339l/34._SX50_.jpg"}},{"id":"2963845","books_count":"486","ratings_count":"642812","text_reviews_count":"9056","original_publication_year":"1954","original_publication_month":"11","original_publication_day":"11","average_rating":"4.44","best_book":{"@attributes":{"type":"Book"},"id":"15241","title":"The Two Towers (The Lord of the Rings, #2)","author":{"id":"656983","name":"J.R.R. Tolkien"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1298415523l/15241._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1298415523l/15241._SY75_.jpg"}},{"id":"2964424","books_count":"490","ratings_count":"612636","text_reviews_count":"8652","original_publication_year":"1955","original_publication_month":"10","original_publication_day":"20","average_rating":"4.53","best_book":{"@attributes":{"type":"Book"},"id":"18512","title":"The Return of the King (The Lord of the Rings, #3)","author":{"id":"656983","name":"J.R.R. Tolkien"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1520258755l/18512._SY160_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1520258755l/18512._SY75_.jpg"}},{"id":"26510","books_count":"7","ratings_count":"9851","text_reviews_count":"83","original_publication_year":"2005","original_publication_month":"1","original_publication_day":"1","average_rating":"4.27","best_book":{"@attributes":{"type":"Book"},"id":"25790","title":"The Lord of the Rings Sketchbook","author":{"id":"9545","name":"Alan  Lee"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1416453209l/25790._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1416453209l/25790._SX50_.jpg"}},{"id":"4479","books_count":"7","ratings_count":"24776","text_reviews_count":"99","original_publication_year":"2002","original_publication_month":"6","original_publication_day":"12","average_rating":"4.59","best_book":{"@attributes":{"type":"Book"},"id":"119","title":"The Lord of the Rings: The Art of The Fellowship of the Ring","author":{"id":"60","name":"Gary Russell"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"4414","books_count":"13","ratings_count":"19149","text_reviews_count":"52","original_publication_year":"2003","original_publication_month":"9","original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"4.53","best_book":{"@attributes":{"type":"Book"},"id":"36","title":"The Lord of the Rings: Weapons and Warfare","author":{"id":"5448409","name":"Chris   Smith"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"89369","books_count":"80","ratings_count":"103986","text_reviews_count":"1699","original_publication_year":"1955","original_publication_month":"10","original_publication_day":"20","average_rating":"4.59","best_book":{"@attributes":{"type":"Book"},"id":"30","title":"J.R.R. Tolkien 4-Book Boxed Set: The Hobbit and The Lord of the Rings","author":{"id":"656983","name":"J.R.R. Tolkien"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1346072396l/30._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1346072396l/30._SX50_.jpg"}},{"id":"17150","books_count":"11","ratings_count":"17497","text_reviews_count":"30","original_publication_year":"2003","original_publication_month":"11","original_publication_day":"5","average_rating":"4.59","best_book":{"@attributes":{"type":"Book"},"id":"349254","title":"The Lord of the Rings: The Return of the King: Visual Companion","author":{"id":"10","name":"Jude Fisher"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"521513","books_count":"14","ratings_count":"7816","text_reviews_count":"40","original_publication_year":"2002","original_publication_month":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"4.46","best_book":{"@attributes":{"type":"Book"},"id":"7351","title":"The Lord of the Rings: The Making of the Movie Trilogy","author":{"id":"4941","name":"Brian Sibley"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"17145","books_count":"8","ratings_count":"7566","text_reviews_count":"30","original_publication_year":"2003","original_publication_month":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"4.57","best_book":{"@attributes":{"type":"Book"},"id":"15242","title":"The Lord of the Rings: The Art of The Two Towers","author":{"id":"60","name":"Gary Russell"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"90550","books_count":"13","ratings_count":"4561","text_reviews_count":"26","original_publication_year":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_month":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"4.51","best_book":{"@attributes":{"type":"Book"},"id":"15221","title":"The Lord of the Rings: The Two Towers: Visual Companion","author":{"id":"10","name":"Jude Fisher"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"808427","books_count":"13","ratings_count":"5503","text_reviews_count":"35","original_publication_year":"2001","original_publication_month":"1","original_publication_day":"1","average_rating":"4.49","best_book":{"@attributes":{"type":"Book"},"id":"15222","title":"The Lord of the Rings: The Fellowship of the Ring: Visual Companion","author":{"id":"10","name":"Jude Fisher"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"1637201","books_count":"10","ratings_count":"6270","text_reviews_count":"29","original_publication_year":"2001","original_publication_month":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"4.39","best_book":{"@attributes":{"type":"Book"},"id":"15239","title":"The Lord of the Rings: Official Movie Guide","author":{"id":"4941","name":"Brian Sibley"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"17136","books_count":"5","ratings_count":"4455","text_reviews_count":"29","original_publication_year":"2005","original_publication_month":"10","original_publication_day":"17","average_rating":"4.35","best_book":{"@attributes":{"type":"Book"},"id":"15232","title":"The Lord of the Rings: A Readers Companion","author":{"id":"9498","name":"Wayne G. Hammond"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1396781282l/15232._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1396781282l/15232._SY75_.jpg"}},{"id":"65478","books_count":"6","ratings_count":"7666","text_reviews_count":"32","original_publication_year":"2004","original_publication_month":"1","original_publication_day":"1","average_rating":"4.54","best_book":{"@attributes":{"type":"Book"},"id":"67514","title":"The Lord of the Rings: The Art of the Return of the King","author":{"id":"60","name":"Gary Russell"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"24584","books_count":"2","ratings_count":"2113","text_reviews_count":"26","original_publication_year":"2004","original_publication_month":"5","original_publication_day":"12","average_rating":"4.02","best_book":{"@attributes":{"type":"Book"},"id":"23640","title":"Understanding The Lord of the Rings: The Best of Tolkien Criticism","author":{"id":"461328","name":"Rose A. Zimbardo"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1382941288l/23640._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1382941288l/23640._SY75_.jpg"}},{"id":"306544","books_count":"52","ratings_count":"5661","text_reviews_count":"437","original_publication_year":"1969","original_publication_month":{"@attributes":{"type":"integer","nil":"true"}},"original_publication_day":{"@attributes":{"type":"integer","nil":"true"}},"average_rating":"3.13","best_book":{"@attributes":{"type":"Book"},"id":"15348","title":"Bored of the Rings: A Parody of J.R.R. Tolkiens Lord of the Rings","author":{"id":"4616903","name":"The Harvard Lampoon"},"image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1309287397l/15348._SX98_.jpg","small_image_url":"https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1309287397l/15348._SY75_.jpg"}},{"id":"17141","books_count":"4","ratings_count":"1119","text_reviews_count":"12","original_publication_year":"2004","original_publication_month":"1","original_publication_day":"1","average_rating":"4.52","best_book":{"@attributes":{"type":"Book"},"id":"15237","title":"The Art of The Lord of the Rings","author":{"id":"60","name":"Gary Russell"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}},{"id":"68766","books_count":"8","ratings_count":"742","text_reviews_count":"29","original_publication_year":"2002","original_publication_month":"1","original_publication_day":"1","average_rating":"4.26","best_book":{"@attributes":{"type":"Book"},"id":"70990","title":"The Lord of the Rings Location Guidebook","author":{"id":"16004017","name":"Ian  Brodie"},"image_url":"https://s.gr-assets.com/assets/nophoto/book/111x148-bcc042a9c91a29c1d680899eff700a03.png","small_image_url":"https://s.gr-assets.com/assets/nophoto/book/50x75-a91bf249278a81aabab721ef782c4a74.png"}}]';
                $obj = json_decode($strn);
                return new JsonResponse([
                    "status" => "ready",
                    "number" => mt_rand(0, 1000),
                    "data" => $obj,
                ]);
            default:
                break;
        }
        return $this->respond_test();
    }

    public function respond_test()
    {
        $tpl_name = "@jeb_snahp/test/base.html";
        return $this->helper->render($tpl_name, "test");
    }

    public function respond_imgcompare($cfg)
    {
        return $this->helper->render($cfg["tpl_name"], $cfg["title"]);
    }
}
