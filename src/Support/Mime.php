<?php

namespace Muyu\Support;

class Mime {

    const map = [
        'aac'   => 'audio/aac',
        'avi'   => 'video/x-msvideo',
        'bmp'   => 'image/bmp',
        'bz'    => 'application/x-bzip',
        'bz2'   => 'application/x-bzip2',
        'css'   => 'text/css',
        'csv'   => 'text/csv',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'   => 'image/gif',
        'htm'   => 'text/html',
        'html'  => 'text/html',
        'ico'   => 'image/vnd.microsoft.icon',
        'jar'   => 'application/java-archive',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'js'    => 'text/javascript',
        'json'  => 'application/json',
        'mp3'   => 'audio/mpeg',
        'png'   => 'image/png',
        'pdf'   => 'application/pdf',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar'   => 'application/x-rar-compressed',
        'svg'   => 'image/svg+xml',
        'tar'   => 'application/x-tar',
        'tif'   => 'image/tiff',
        'tiff'  => 'image/tiff',
        'ttf'   => 'font/ttf',
        'txt'   => 'text/plain',
        'vsd'   => 'application/vnd.visio',
        'wav'   => 'audio/wav',
        'webm'  => 'audio/webm',
        'webp'  => 'image/webp',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml'   => 'application/xml',
        'zip'   => 'application/zip',
        '7z'    => 'application/x-7z-compressed',
    ];

    public static function mime($ext) {
        return self::map[$ext] ?? null;
    }

}