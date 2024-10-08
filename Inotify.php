<?php

declare(strict_types=1);

namespace Luzrain\Polyfill\Inotify;

final class Inotify
{
    public const IN_ACCESS = 0x00000001; // File was accessed
    public const IN_MODIFY = 0x00000002; // File was modified
    public const IN_ATTRIB = 0x00000004; // Metadata changed
    public const IN_CLOSE_WRITE = 0x00000008; // Writtable file was closed
    public const IN_CLOSE_NOWRITE = 0x00000010; // Unwrittable file closed
    public const IN_OPEN = 0x00000020; // File was opened
    public const IN_MOVED_FROM = 0x00000040; // File was moved from X
    public const IN_MOVED_TO = 0x00000080; // File was moved to Y
    public const IN_CREATE = 0x00000100; // Subfile was created
    public const IN_DELETE = 0x00000200; // Subfile was deleted
    public const IN_DELETE_SELF = 0x00000400; // Self was deleted
    public const IN_MOVE_SELF = 0x00000800; // Self was moved
    public const IN_UNMOUNT = 0x00002000; // Backing fs was unmounted
    public const IN_Q_OVERFLOW = 0x00004000; // Event queued overflowed
    public const IN_IGNORED = 0x00008000; // File was ignored
    public const IN_CLOSE = (self::IN_CLOSE_WRITE | self::IN_CLOSE_NOWRITE); // Close
    public const IN_MOVE = (self::IN_MOVED_FROM | self::IN_MOVED_TO); // Moves
    public const IN_ONLYDIR = 0x01000000; // Only watch the path if it is a directory
    public const IN_DONT_FOLLOW = 0x02000000; // Do not follow a sym link
    public const IN_EXCL_UNLINK = 0x04000000; // Exclude events on unlinked objects
    public const IN_MASK_ADD = 0x20000000; // Add to the mask of an already existing watch
    public const IN_ISDIR = 0x40000000; // Event occurred against dir
    public const IN_ONESHOT = 0x80000000; // Only send event once
    public const IN_ALL_EVENTS = ( // All events which a program can wait on
        self::IN_ACCESS |
        self::IN_MODIFY |
        self::IN_ATTRIB |
        self::IN_CLOSE_WRITE |
        self::IN_CLOSE_NOWRITE |
        self::IN_OPEN |
        self::IN_MOVED_FROM |
        self::IN_MOVED_TO |
        self::IN_CREATE |
        self::IN_DELETE |
        self::IN_DELETE_SELF |
        self::IN_MOVE_SELF
    );

    /**
     * https://gist.github.com/kaiwan/cd4985f3dbfeb8dfc44d1f0e4ee67dec
     */
    private const EMFILE = 24;
    private const ENFILE = 23;
    private const ENOMEM = 12;
    private const EACCES = 13;
    private const EBADF = 9;
    private const EINVAL = 22;
    private const ENOSPC = 28;
    private const EAGAIN = 11;

    /*
     * Define some error messages for the error numbers set by inotify_*() functions, as strerror() messages are not always usefull here
     */
    private const INOTIFY_INIT_EMFILE = 'inotify_init(): The user limit on the total number of inotify instances has been reached';
    private const INOTIFY_INIT_ENFILE = 'inotify_init(): The system limit on the total number of file descriptors has been reached';
    private const INOTIFY_INIT_ENOMEM = 'inotify_init(): Insufficient kernel memory is available';
    private const INOTIFY_ADD_WATCH_EACCES = 'inotify_add_watch(): Read access to the given file is not permitted';
    private const INOTIFY_ADD_WATCH_EBADF = 'inotify_add_watch(): The given file descriptor is not valid';
    private const INOTIFY_ADD_WATCH_EINVAL = 'inotify_add_watch(): The given event mask contains no valid events; or the given file descriptor is not valid';
    private const INOTIFY_ADD_WATCH_ENOMEM = 'inotify_add_watch(): Insufficient kernel memory was available';
    private const INOTIFY_ADD_WATCH_ENOSPC = 'inotify_add_watch(): The user limit on the total number of inotify watches was reached or the kernel failed to allocate a needed resource';
    private const INOTIFY_RM_WATCH_EINVAL  = 'inotify_rm_watch(): The file descriptor is not an inotify instance or the watch descriptor is invalid';

    private const FIONREAD = 0x541B;

    private static \FFI $ffi;

    private function __construct()
    {
    }

    private static function ffi(): \FFI
    {
        return self::$ffi ??= \FFI::load(__DIR__ . '/inotify.h');
    }

    /**
     * @return resource|false
     */
    public static function inotify_init(): mixed
    {
        $fd = self::ffi()->inotify_init();

        if ($fd === -1) {
            $error = match (self::ffi()->errno) {
                self::EMFILE => self::INOTIFY_INIT_EMFILE,
                self::ENFILE => self::INOTIFY_INIT_ENFILE,
                self::ENOMEM => self::INOTIFY_INIT_ENOMEM,
                default => self::strerror(self::ffi()->errno),
            };
            \trigger_error($error, E_USER_WARNING);

            return false;
        }

        return \fopen(\sprintf('inotify://%d', $fd), 'r');
    }

    /**
     * @param resource $inotify_instance
     */
    public static function inotify_add_watch(mixed $inotify_instance, string $pathname, int $mask): int|false
    {
        if (!(\is_resource($inotify_instance) && \get_resource_type($inotify_instance) === 'stream')) {
            throw new \TypeError(\sprintf('$inotify_instance must be of type resource, %s given', \get_debug_type($inotify_instance)));
        }

        $fd = StreamWrapper::fdFromStream($inotify_instance);
        $watchDescriptor = self::ffi()->inotify_add_watch($fd, $pathname, $mask);

        if ($watchDescriptor === -1) {
            $error = match (self::ffi()->errno) {
                self::EACCES => self::INOTIFY_ADD_WATCH_EACCES,
                self::EBADF => self::INOTIFY_ADD_WATCH_EBADF,
                self::EINVAL => self::INOTIFY_ADD_WATCH_EINVAL,
                self::ENOMEM => self::INOTIFY_ADD_WATCH_ENOMEM,
                self::ENOSPC => self::INOTIFY_ADD_WATCH_ENOSPC,
                default => self::strerror(self::ffi()->errno),
            };
            \trigger_error($error, E_USER_WARNING);

            return false;
        }

        return $watchDescriptor;
    }

    private static function php_inotify_queue_len(int $fd): int
    {
        $queueLen = self::ffi()->new('int');

        $ret = self::ffi()->ioctl($fd, self::FIONREAD, \FFI::addr($queueLen));

        if ($ret === -1) {
            \trigger_error(self::strerror(self::ffi()->errno), E_USER_WARNING);

            return 0;
        }

        return $queueLen->cdata;
    }

    /**
     * @param resource $inotify_instance
     */
    public static function inotify_queue_len(mixed $inotify_instance): int
    {
        if (!(\is_resource($inotify_instance) && \get_resource_type($inotify_instance) === 'stream')) {
            throw new \TypeError(\sprintf('$inotify_instance must be of type resource, %s given', \get_debug_type($inotify_instance)));
        }

        return self::php_inotify_queue_len(StreamWrapper::fdFromStream($inotify_instance));
    }

    /**
     * @param resource $inotify_instance
     * @return list<array{wd: int, mask: int, cookie: int, name: string}>|false
     */
    public static function inotify_read(mixed $inotify_instance): array|false
    {
        if (!(\is_resource($inotify_instance) && \get_resource_type($inotify_instance) === 'stream')) {
            throw new \TypeError(\sprintf('$inotify_instance must be of type resource, %s given', \get_debug_type($inotify_instance)));
        }

        $fd = StreamWrapper::fdFromStream($inotify_instance);

        $inotifyEventType = self::ffi()->type('struct inotify_event');
        $inotifyEventPtrType = self::ffi()->type('struct inotify_event *');

        $bufSize = (int) \ceil(self::php_inotify_queue_len($fd) * 1.6);
        if ($bufSize < 1) {
            $bufSize = \FFI::sizeof($inotifyEventType) + 32;
        }

        while (true) {
            $readbuf = self::ffi()->new(\FFI::arrayType(self::ffi()->type('char'), [$bufSize]));
            $readden = self::ffi()->read($fd, $readbuf, $bufSize);

            if ($readden === -1) {
                // buf too small to read an event
                if (self::ffi()->errno === self::EINVAL) {
                    $bufSize = (int) \ceil($bufSize * 1.6);
                    continue;
                }
                // fd is unblocking, and no event is available
                if (self::ffi()->errno === self::EAGAIN) {
                    return false;
                }
            }

            break;
        }

        $events = [];
        for ($i = 0; $i < $readden; $i += \FFI::sizeof($inotifyEventType) + $event->len) {
            $event = self::ffi()->cast($inotifyEventPtrType, \FFI::addr($readbuf[$i]));
            $events[] = [
                'wd' => $event->wd,
                'mask' => $event->mask,
                'cookie' => $event->cookie,
                'name' => $event->len > 0 ? \FFI::string($event->name) : '',
            ];
        }

        return $events;
    }

    /**
     * @param resource $inotify_instance
     */
    public static function inotify_rm_watch(mixed $inotify_instance, int $watch_descriptor): bool
    {
        if (!(\is_resource($inotify_instance) && \get_resource_type($inotify_instance) === 'stream')) {
            throw new \TypeError(\sprintf('$inotify_instance must be of type resource, %s given', \get_debug_type($inotify_instance)));
        }

        $fd = StreamWrapper::fdFromStream($inotify_instance);
        $ret = self::ffi()->inotify_rm_watch($fd, $watch_descriptor);

        if ($ret === -1) {
            $error = match (self::ffi()->errno) {
                self::EINVAL => self::INOTIFY_RM_WATCH_EINVAL,
                default => self::strerror(self::ffi()->errno),
            };
            \trigger_error($error, E_USER_WARNING);

            return false;
        }

        return true;
    }

    public static function closeFD(int $fd): void
    {
        self::ffi()->close($fd);
    }

    private static function strerror(int $errno): string
    {
        return \function_exists('posix_strerror') ? \posix_strerror($errno) : \sprintf('errno=%d', $errno);
    }
}
