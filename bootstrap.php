<?php

declare(strict_types=1);

use Luzrain\Polyfill\Inotify\Inotify;
use Luzrain\Polyfill\Inotify\StreamWrapper;

if (extension_loaded('inotify')) {
    return;
}

stream_wrapper_register('inotify', StreamWrapper::class, STREAM_IS_URL);

if (!defined('IN_ACCESS')) { define('IN_ACCESS', Inotify::IN_ACCESS); }
if (!defined('IN_MODIFY')) { define('IN_MODIFY', Inotify::IN_MODIFY); }
if (!defined('IN_ATTRIB')) { define('IN_ATTRIB', Inotify::IN_ATTRIB); }
if (!defined('IN_CLOSE_WRITE')) { define('IN_CLOSE_WRITE', Inotify::IN_CLOSE_WRITE); }
if (!defined('IN_CLOSE_NOWRITE')) { define('IN_CLOSE_NOWRITE', Inotify::IN_CLOSE_NOWRITE); }
if (!defined('IN_OPEN')) { define('IN_OPEN', Inotify::IN_OPEN); }
if (!defined('IN_MOVED_FROM')) { define('IN_MOVED_FROM', Inotify::IN_MOVED_FROM); }
if (!defined('IN_MOVED_TO')) { define('IN_MOVED_TO', Inotify::IN_MOVED_TO); }
if (!defined('IN_CREATE')) { define('IN_CREATE', Inotify::IN_CREATE); }
if (!defined('IN_DELETE')) { define('IN_DELETE', Inotify::IN_DELETE); }
if (!defined('IN_DELETE_SELF')) { define('IN_DELETE_SELF', Inotify::IN_DELETE_SELF); }
if (!defined('IN_MOVE_SELF')) { define('IN_MOVE_SELF', Inotify::IN_MOVE_SELF); }
if (!defined('IN_UNMOUNT')) { define('IN_UNMOUNT', Inotify::IN_UNMOUNT); }
if (!defined('IN_Q_OVERFLOW')) { define('IN_Q_OVERFLOW', Inotify::IN_Q_OVERFLOW); }
if (!defined('IN_IGNORED')) { define('IN_IGNORED', Inotify::IN_IGNORED); }
if (!defined('IN_CLOSE')) { define('IN_CLOSE', Inotify::IN_CLOSE); }
if (!defined('IN_MOVE')) { define('IN_MOVE', Inotify::IN_MOVE); }
if (!defined('IN_ONLYDIR')) { define('IN_ONLYDIR', Inotify::IN_ONLYDIR); }
if (!defined('IN_DONT_FOLLOW')) { define('IN_DONT_FOLLOW', Inotify::IN_DONT_FOLLOW); }
if (!defined('IN_EXCL_UNLINK')) { define('IN_EXCL_UNLINK', Inotify::IN_EXCL_UNLINK); }
if (!defined('IN_MASK_ADD')) { define('IN_MASK_ADD', Inotify::IN_MASK_ADD); }
if (!defined('IN_ISDIR')) { define('IN_ISDIR', Inotify::IN_ISDIR); }
if (!defined('IN_ONESHOT')) { define('IN_ONESHOT', Inotify::IN_ONESHOT); }
if (!defined('IN_ALL_EVENTS')) { define('IN_ALL_EVENTS', Inotify::IN_ALL_EVENTS); }

if (!function_exists('inotify_init')) {
    function inotify_init(): mixed {
        return Inotify::inotify_init();
    }
}

if (!function_exists('inotify_add_watch')) {
    function inotify_add_watch(mixed $inotify_instance, string $pathname, int $mask): int|false {
        return Inotify::inotify_add_watch($inotify_instance, $pathname, $mask);
    }
}

if (!function_exists('inotify_queue_len')) {
    function inotify_queue_len(mixed $inotify_instance): int {
        return Inotify::inotify_queue_len($inotify_instance);
    }
}

if (!function_exists('inotify_read')) {
    function inotify_read(mixed $inotify_instance): array|false {
        return Inotify::inotify_read($inotify_instance);
    }
}

if (!function_exists('inotify_rm_watch')) {
    function inotify_rm_watch(mixed $inotify_instance, int $watch_descriptor): bool {
        return Inotify::inotify_rm_watch($inotify_instance, $watch_descriptor);
    }
}
