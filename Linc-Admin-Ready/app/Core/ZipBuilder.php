<?php

declare(strict_types=1);

namespace Linc\Core;

use BusinessException;

/**
 * 纯 PHP ZIP 生成器（deflate 压缩，gzdeflate 为 PHP 内置 zlib），
 * 
 */
final class ZipBuilder
{
    /** @var list<array{name: string, data: string}> */
    private array $entries = [];

    public function add(string $name, string $data): void
    {
        $this->entries[] = ['name' => $name, 'data' => $data];
    }

    public function build(): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;

        foreach ($this->entries as $entry) {
            $nameBytes = $entry['name'];
            $data = $entry['data'];
            $crc = crc32($data);
            $size = strlen($data);
            $deflated = gzdeflate($data, 6);
            $compressed = $deflated === false ? $data : $deflated;
            $compressedSize = strlen($compressed);
            $method = 0x0008; // deflate

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,       // local file header signature
                20,               // version needed
                0x0800,           // flags: UTF-8 name
                $method,
                0,                // mod time
                0x21,             // mod date (1980-01-01)
                $crc,
                $compressedSize,
                $size,
                strlen($nameBytes),
                0                 // extra length
            );
            $local .= $localHeader . $nameBytes . $compressed;

            $centralHeader = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,       // central directory signature
                20,               // version made by
                20,               // version needed
                0x0800,           // flags
                $method,
                0,
                0x21,
                $crc,
                $compressedSize,
                $size,
                strlen($nameBytes),
                0, 0, 0,          // extra, comment, disk
                0,                // internal attrs
                0,                // external attrs
                $offset
            );
            $central .= $centralHeader . $nameBytes;
            $offset += strlen($localHeader) + strlen($nameBytes) + $compressedSize;
            $count++;
        }

        $end = pack(
            'VvvvvVVv',
            0x06054b50,       // end of central directory
            0, 0,             // disk numbers
            $count,
            $count,
            strlen($central),
            $offset,
            0                 // comment length
        );

        return $local . $central . $end;
    }
}
