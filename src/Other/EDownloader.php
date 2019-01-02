<?php
namespace Muyu\Other;

use Muyu\Curl;
use Muyu\Informal\Downloader;
use Muyu\Informal\Traits\DownloaderTrait;
use function Muyu\Support\Fun\conf;
use Muyu\Support\Tool;


class EDownloader extends Downloader
{
    use DownloaderTrait;
    function __construct($url = null, $folder = null, $size = null) {
        parent::__construct($url, $folder, $size);
    }
    function downloadFromMoodrainGroup($group = 'toDownload', $folder = null, $cookie = null) {
        if(!$cookie)
            $cookie = conf('moodraincn.default.cookie');
        else
            $cookie = ['laravel_session' => $cookie];
        $groups = json_decode(json_decode((new Curl('https://moodrain.cn/get-user-data'))->cookie($cookie)->get())->data)->groups;
        $theGroup = null;
        $links = [];
        foreach($groups as $g)
            if($g->name == $group)
                $theGroup = $g;
        if(!$theGroup)
            Tool::dd('group not found');
        foreach($theGroup->links as $link)
            $links[] = $link->url;
        $count = count($links);
        $finish = 1;
        foreach($links as $link) {
            (new Downloader($link, $folder))->auto();
            echo PHP_EOL .'Finish ' . $finish++ . '/' . $count . ' of ' .  $group . ' Group' . PHP_EOL;
        }
    }
    function auto($option = []) {
        $downloader = $this;
        if(strpos($this->url, 'e-hentai.org'))
            $downloader = $downloader->ESite();
        if(strpos($this->url, 'nhentai.net'))
            $downloader = $downloader->NSite();
        if(strpos($this->url, 'hitomi.la'))
            $downloader = $downloader->HSite();
        if($downloader !== $this)
            $downloader->defaultRun($option);
    }
    function NSite() {
        return new class($this->url, $this->folder, $this->size) {
            use downloaderTrait;
            function __construct($url = null, $folder = null, $size = null) {
                $this->setBasicField($url, $folder, $size);
            }
            private function runCheck($option) {
                $this->check('url');
            }
            private function runHandle($option) {
                $metaReq = new Curl($this->url);
                $downReq = new Curl();
                if(isset($option['ss']) && $option['ss']) {
                    $downReq->ss();
                    $metaReq->ss();
                }
                $meta = $metaReq->get();
                $title = Tool::strBetween($meta, '<h2>', '</h2>');
                $count = $this->size = (int) Tool::strBetween($meta, 'num_pages":', '})');
                if(!$metaReq->is200() || !$count)
                    Tool::dd($metaReq);
                $galleryId = Tool::strBetween($meta, 'data-src="https://t.nhentai.net/galleries/', '/cover');
                $ext = Tool::ext(Tool::strBetween($meta, 'data-src="https://t.nhentai.net/galleries/', '" src='));
                $this->folder = $this->folder . '/' . Tool::dirFilter($title);
                if(file_exists(($this->folder)))
                    $this->folder .= uniqid();
                Tool::mkdir($this->folder);
                $tryExt = ['jpg', 'png', 'gif'];
                for($i = 1;$i <= $count;$i++) {
                    $downReq->url("https://i.nhentai.net/galleries/$galleryId/$i.$ext")->get();
                    if($this->goNext($downReq))
                        $this->save($i . '.' . $ext, $downReq);
                    else {
                        if($downReq->is404()) {
                            foreach($tryExt as $newExt) {
                                if($newExt != $ext) {
                                    if($this->goNext($downReq->url("https://i.nhentai.net/galleries/$galleryId/$i.$newExt")->get(false)))
                                        $this->save($i . '.' . $newExt, $downReq);
                                }
                            }
                        }
                    };
                }
            }
            private function runFinish() {
                $this->checkIntegrity();
            }
        };
    }
    function ESite() {
        return new class($this->url, $this->folder, $this->size) {
            use downloaderTrait;
            private $id;
            private $hash;
            function __construct($url = null, $folder = null, $size = null) {
                $this->setBasicField($url, $folder, $size);
                $this->id = conf('eSite.id');
                $this->hash = conf('eSite.hash');
            }
            function id($id) {
                $this->id = $id;
                return $this;
            }
            function hash($hash) {
                $this->hash = $hash;
                return $this;
            }
            private function runCheck() {
                $this->check(['url', 'id', 'hash']);
            }
            private function runHandle($option) {
                $cookie = [
                    'ipb_member_id' => $this->id,
                    'ipb_pass_hash' => $this->hash,
                ];
                $curl = (new Curl($this->url))->cookie($cookie);
                if(isset($option['ss']) && $option['ss'])
                    $curl->ss();
                $realUrl = 'href="https://e-hentai.org/s/' . Tool::strBetween($curl->get(), 'href="https://e-hentai.org/s/', '">');
                [,,,,$pathKey, $file] = explode('/', $realUrl);
                $galleryId = explode('-', $file)[0];
                $curl->url("https://e-hentai.org/s/$pathKey/$galleryId-1")->get();
                $this->folder = $this->folder . '/' . Tool::dirFilter($curl->title());
                if(file_exists(($this->folder)))
                    $this->folder .= uniqid();
                Tool::mkdir($this->folder);
                $count = $this->size = (int) Tool::strBetween($curl->content(), 'span> / <span>', '</span>');
                $try = array_fill(1, $count, 10);
                for($i = 1;$i <= $count;$i++) {
                    echo $i;
                    $nextPage = $i + 1;
                    $infoReq = (new Curl("https://e-hentai.org/s/$pathKey/$galleryId-$i"))->cookie($cookie);
                    if(isset($option['ss']) && $option['ss'])
                        $infoReq->ss();
                    $infoReq->get();
                    if(!$this->goNext($infoReq)) {
                        if($try[$i]-- > 0)
                            $i--;
                        continue;
                    }
                    $oldPathKey = $pathKey;
                    $pathKey = Tool::strBetween($infoReq->content(), "return load_image($nextPage, '", "')");
                    $info = [];
                    parse_str(str_replace('&amp;', '&', Tool::strBetween($infoReq->content(), 'f="https://e-hentai.org/fullimg.php?', '">Download original')), $info);
                    $gid = $info['gid'] ?? null;
                    $key = $info['key'] ?? null;
                    $url = !$gid || !$key ? Tool::strBetween($infoReq->content(), 'id="img" src="', '" style="') : "https://e-hentai.org/fullimg.php?gid=$gid&page=$i&key=$key";
                    $downReq = (new Curl($url))->cookie($cookie);
                    if(isset($option['ss']) && $option['ss'])
                        $downReq->ss();
                    $downReq->get();
                    if($this->goNext($downReq))
                        $this->save($i . '.jpg', $downReq);
                    else {
                        if($try[$i]-- > 0) {
                            $pathKey = $oldPathKey;
                            $i--;
                        }
                        continue;
                    }
                    if(!$pathKey) {
                        if($i == $count)
                            break;
                        else if($try[$i]-- > 0) {
                            $pathKey = $oldPathKey;
                            $i--;
                        }
                        else
                            break;
                    }
                }
            }
            private function runFinish() {
                $this->checkIntegrity();
            }
        };
    }
    function HSite() {
        return new class($this->url, $this->folder, $this->size) {
            use downloaderTrait;
            function __construct($url = null, $folder = null, $size = null) {
                $this->setBasicField($url, $folder, $size);
            }
            private function runCheck() {
                $this->check('url');
            }
            private function runHandle($option) {
                $infoReq = new Curl();
                $titleReq = new Curl($this->url);
                $downReq = new Curl();
                if(isset($option['ss']) && $option['ss']) {
                    $infoReq->ss();
                    $titleReq->ss();
                    $downReq->ss();
                }
                $galleryId = Tool::name(basename($this->url));
                $info = $infoReq->url('https://ltn.hitomi.la/galleries/' . $galleryId . '.js')->get();
                $rawList = json_decode(str_replace('var galleryinfo = ', '', $info));
                $list = [];
                foreach($rawList as $l)
                    $list[] = $l->name;
                if(!$list)
                    Tool::dd('get list failed');
                $count = $this->size = count($list);
                $title = Tool::strBetween($titleReq->get(), '<h1><a href="/reader/' . $galleryId . '.html">', '</a>');
                $this->folder = $this->folder . '/' . Tool::dirFilter($title);
                $abId = $galleryId . '';
                $abId = $abId{strlen($abId)-1};
                $abId = $abId == 1 ? 0 : $abId;
                $downReq->url('https://' . ($abId % 2 ? 'b' : 'a') . 'a.hitomi.la/galleries/' . $galleryId . '/');
                if(file_exists(($this->folder)))
                    $this->folder .= uniqid();
                Tool::mkdir($this->folder);
                foreach($list as $item) {
                    $downReq->path($item)->get();
                    $this->save($item, $downReq);
                }
            }
            private function runFinish() {
                $this->checkIntegrity();
            }
        };
    }
}