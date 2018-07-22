<?php
namespace Muyu\Support;

use App\Support\Traits\DownloaderTrait;
use Muyu\Config;
use Muyu\Curl;
use Muyu\Tool;
class Downloader
{
    private $opt;
    use DownloaderTrait;

    public function run(string $mode = 'order') : void
    {
        switch($mode)
        {
            case 'order': $this->orderDownload();break;
            case 'e':     $this->eSite()->run();break;
            case 'n':     $this->nSite()->run();break;
            default:      die('unknown mode');
        }
    }
    private function orderDownload() : void
    {
        $this->check('url');
        $this->folder = $this->folder ?? './storage/download';
        Tool::mkdir($this->folder);
        $curl = new Curl();
        for($i = 1;$i <= $this->size;$i++)
        {
            $curl->url(sprintf($this->url, $i))->get(false);
            if($this->goNext($curl))
                $this->save($i . '.' . Tool::ext($this->url), $curl);
            else
                break;
        }
        $curl->close();
    }

    public function NSite() : object
    {
        return new class($this->url, $this->folder, $this->size)
        {
            use downloaderTrait;
            public function __construct(string $url = null, string $folder = null, int $size = null)
            {
                $this->setBasicField($url, $folder, $size);
            }
            function run() : void
            {
                $this->check('url');
                [,,,,$galleryId] = explode('/', $this->url);
                $ext = Tool::ext($this->url);
                $this->folder = $this->folder ?? $galleryId;
                Tool::mkdir($this->folder);
                $curl = new Curl();
                for($i = 1;$i <= $this->size;$i++)
                {
                    $curl->url("https://i.nhentai.net/galleries/$galleryId/$i.$ext")->get(false);
                    if($this->goNext($curl))
                        $this->save($i . '.' . $ext, $curl);
                    else
                        break;
                }
                $curl->close();
            }
        };
    }

    public function ESite() : object
    {
        return new class($this->url, $this->folder, $this->size, $this->opt)
        {
            use downloaderTrait;
            private $opt;
            private $id;
            private $hash;
            public function __construct(string $url = null, string $folder = null, int $size = null, array $opt = [])
            {
                $config = new Config();
                $this->setBasicField($url, $folder, $size);
                $this->opt = $opt;
                $this->id = $opt['id'] ?? $config->try('eSite.id');
                $this->hash = $opt['hash'] ?? $config->try('eSite.hash');
            }
            function id(string $id) : object
            {
                $this->id = $id;
                return $this;
            }
            function hash(string $hash) : object
            {
                $this->hash = $hash;
                return $this;
            }
            function run() : void
            {
                $this->check(['url', 'id', 'hash']);
                [,,,,$pathKey, $file] = explode('/', $this->url);
                $galleryId = explode('-', $file)[0];
                $curl = (new Curl())->cookie([
                    'ipb_member_id' => $this->id,
                    'ipb_pass_hash' => $this->hash,
                ])->ss();
                for($i = 1;$i <= $this->size;$i++)
                {
                    $nextPage = $i + 1;
                    $curl->url("https://e-hentai.org/s/$pathKey/$galleryId-$i")->get(false);
                    if(!$this->goNext($curl))
                        break;
                    if($i === 1)
                    {
                        $this->folder = $this->folder ?? $curl->title();
                        Tool::mkdir($this->folder);
                    }
                    $pathKey = Tool::strBetween($curl->content(), "return load_image($nextPage, '", "')");
                    $info = [];
                    parse_str(str_replace('&amp;', '&', Tool::strBetween($curl->content(), 'f="https://e-hentai.org/fullimg.php?', '">Download original')), $info);
                    $gid = $info['gid'] ?? null;
                    $key = $info['key'] ?? null;
                    $url = !$gid || !$key ? Tool::strBetween($curl->content(), 'id="img" src="', '" style="') : "https://e-hentai.org/fullimg.php?gid=$gid&page=$i&key=$key";
                    $curl->url($url)->get(false);
                    if($this->goNext($curl))
                        $this->save($i . '.jpg', $curl);
                    else
                        break;
                    if(!$pathKey)
                        break;
                }
                $curl->close();
            }
        };
    }

    public function __construct(string $url = null, string $folder = null, int $size = null, array $opt = [])
    {
        $this->url = $url;
        $this->size = $size ?? 9999;
        $this->folder = $folder;
        $this->opt = $opt;
        set_time_limit(0);
    }
    public function url(string $url) : Downloader
    {
        $this->url = $url;
        return $this;
    }
    public function size(int $size) : Downloader
    {
        $this->size = $size;
        return $this;
    }
    public function folder(string $folder) : Downloader
    {
        $this->folder = $folder;
        return $this;
    }
    public function opt(array $opt) : Downloader
    {
        $this->opt = $opt;
        return $this;
    }

}