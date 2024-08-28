<?php

declare(strict_types=1);

namespace Luzrain\Polyfill\Inotify;

final class StreamWrapper
{
    private int $fd = 0;

    /**
     * @var resource
     */
    private mixed $stream;

    /**
     * @var resource
     */
    public mixed $context;

    /**
     * @param resource $stream
     */
    public static function fdFromStream(mixed $stream): int
    {
        $wrapper = \stream_get_meta_data($stream)['wrapper_data'] ?? null;

        if ($wrapper instanceof self) {
            return $wrapper->fd;
        }

        return 0;
    }

    /**
     * @return resource
     */
    public function stream_cast(int $cast_as): mixed
    {
        return $this->stream;
    }

    public function stream_close(): void
    {
        \fclose($this->stream);
        Inotify::closeFD($this->fd);
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public function stream_lock(int $operation): bool
    {
        return \flock($this->stream, $operation);
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $fd = \substr($path, \strlen('inotify://'));
        if (!\is_numeric($fd)) {
            \trigger_error(\sprintf('Invalid file descriptor in "%s": "%s"', $path, $fd), E_USER_WARNING);

            return false;
        }

        if (false === $stream = \fopen(\sprintf('php://fd/%d', $fd), 'r')) {
            return false;
        }

        $this->fd = (int) $fd;
        $this->stream = $stream;

        return true;
    }

    public function stream_read(int $count): string|false
    {
        return '';
    }

    public function stream_set_option(int $option, ?int $arg1, ?int $arg2): bool
    {
        return match ($option) {
            STREAM_OPTION_BLOCKING => \stream_set_blocking($this->stream, (bool) $arg1),
            default => false,
        };
    }

    public function stream_stat(): array|false
    {
        return false;
    }
}
