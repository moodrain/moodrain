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

    function inOrder() : void
    {
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

    function byStream($context, $filename = null)
    {
        $stream = fopen($this->url, 'r', false, stream_context_create($context));
        $filename = $filename ?? basename(explode('?', $this->url)[0]) ?? 'download';
        $file = fopen($this->folder . '/' . $filename, 'w');
        $buffer = null;
        while(true)
        {
            $buffer = fgets($stream, 1024000);
            fwrite($file, $buffer);
            if(feof($stream))
                break;
        }
        fclose($stream);
        fclose($file);
    }

    function NSite() : object
    {
        return new class($this->url, $this->folder, $this->size)
        {
            use downloaderTrait;
            function __construct(string $url = null, string $folder = null, int $size = null)
            {
                $this->setBasicField($url, $folder, $size);
            }
            function run() : void
            {
                $this->check('url');
                $galleryId = explode('/', $this->url)[4];
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

    function ESite() : object
    {
        return new class($this->url, $this->folder, $this->size, $this->opt)
        {
            use downloaderTrait;
            private $opt;
            private $id;
            private $hash;
            function __construct(string $url = null, string $folder = null, int $size = null, array $opt = [])
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

    function __construct(string $url = null, string $folder = null, int $size = null, array $opt = [])
    {
        $this->url = $url;
        $this->size = $size ?? 9999;
        $this->folder = $folder;
        $this->opt = $opt;
        set_time_limit(0);
        $this->check('url');
        $this->folder = $this->folder ?? './storage/download';
        Tool::mkdir($this->folder);
    }
    function url(string $url) : Downloader
    {
        $this->url = $url;
        return $this;
    }
    function size(int $size) : Downloader
    {
        $this->size = $size;
        return $this;
    }
    function folder(string $folder) : Downloader
    {
        $this->folder = $folder;
        return $this;
    }
    function opt(array $opt) : Downloader
    {
        $this->opt = $opt;
        return $this;
    }

}