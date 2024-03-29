<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

use Exception;

class File_container {

    # ─────────────────────────────────────────────────────────────────────
    # An example of a container after executing the code below:
    # ═════════════════════════════════════════════════════════════════════
    #
    #    $handle = fopen('container://'.$path_root.'simple.box:file_1);
    #    fwrite($handle, 'X');
    #    fclose($handle);
    #
    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦
    #
    #    container-head=a:5:{
    #        s:7:"version";d:1;
    #        s:13:"lock_timestmp";i:1000000000;
    #        s:11:"meta_offset";i:271;
    #        s:16:"meta_offset_prev";i:0;
    #        s:8:"checksum";s:32:"00000000000000000000000000000000";
    #    }
    #    X
    #    container-meta=a:1:{
    #        s:6:"file_1";a:2:{
    #            s:6:"length";i:1;
    #            s:6:"offset";i:255;
    #        }
    #    }
    #
    # ─────────────────────────────────────────────────────────────────────

    # ─────────────────────────────────────────────────────────────────────
    # An example of a container after executing the code below:
    # ═════════════════════════════════════════════════════════════════════
    #
    #    $handle = fopen('container://'.$path_root.'simple.box:file_1);
    #    fwrite($handle, 'X');
    #    fclose($handle);
    #    $handle = fopen('container://'.$path_root.'simple.box:file_1);
    #    fwrite($handle, 'Y');
    #    fclose($handle);
    #
    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦
    #
    #    container-head=a:5:{
    #        s:7:"version";d:1;
    #        s:13:"lock_timestmp";i:1000000000;
    #        s:11:"meta_offset";i:348;
    #        s:16:"meta_offset_prev";i:271;
    #        s:8:"checksum";s:32:"00000000000000000000000000000000";
    #    }
    #    X
    #    container-meta=a:1:{
    #        s:6:"file_1";a:2:{
    #            s:6:"length";i:1;
    #            s:6:"offset";i:255;
    #        }
    #    }
    #    Y
    #    container-meta=a:1:{
    #        s:6:"file_1";a:2:{
    #            s:6:"length";i:1;
    #            s:6:"offset";i:332;
    #        }
    #    }
    #
    # ─────────────────────────────────────────────────────────────────────

    const WRAPPER = 'container';
    const META_TITLE = self::WRAPPER.'-meta=';
    const HEAD_TITLE = self::WRAPPER.'-head=';
    const HEAD_TITLE_LENGTH = 15;
    const HEAD_LENGTH = 0xff;

    public $context;

    protected $stream;
    protected $target; # root|file
    protected $path_root;
    protected $path_file;
    protected $mode;
    protected $mode_is_readable = false;
    protected $mode_is_writable = false;
    protected $mode_is_seekable = false;
    protected $meta_parsed      = [];
    protected $was_changed      = false;

    # head properties
    protected $version          = 1.0;
    protected $lock_timestmp    = 1000000000;
    protected $meta_offset      = 0;
    protected $meta_offset_prev = 0;
    protected $checksum         = '00000000000000000000000000000000';

    # meta properties
    protected $length = 0;
    protected $offset = 0;

    function __head_init() {
        fseek($this->stream, static::HEAD_TITLE_LENGTH);
        $head = fread($this->stream, static::HEAD_LENGTH - static::HEAD_TITLE_LENGTH);
        $head_parsed = static::__data_unpack($head);
        if ($head_parsed && array_key_exists('version'         , $head_parsed)) $this->version          = $head_parsed['version'      ];
        if ($head_parsed && array_key_exists('lock_timestmp'   , $head_parsed)) $this->lock_timestmp    = $head_parsed['lock_timestmp'];
        if ($head_parsed && array_key_exists('meta_offset'     , $head_parsed)) $this->meta_offset      = $head_parsed['meta_offset'  ];
        if ($head_parsed && array_key_exists('meta_offset_prev', $head_parsed)) $this->meta_offset_prev = $head_parsed['meta_offset'  ];
        if ($head_parsed && array_key_exists('checksum'        , $head_parsed)) $this->checksum         = $head_parsed['checksum'     ];
    }

    function __meta_init() {
        if ($this->meta_offset !== 0) {
            fseek($this->stream, $this->meta_offset);
            $meta = fread($this->stream, 0xffff);
            $this->meta_parsed = static::__data_unpack($meta);
            if (isset($this->meta_parsed[$this->path_file])) {
                if (array_key_exists('length', $this->meta_parsed[$this->path_file])) $this->length = $this->meta_parsed[$this->path_file]['length'];
                if (array_key_exists('offset', $this->meta_parsed[$this->path_file])) $this->offset = $this->meta_parsed[$this->path_file]['offset'];
            }
        }
    }

    function __head_save() {
        fseek ($this->stream, 0);
        fwrite($this->stream, static::HEAD_TITLE);
        fwrite($this->stream, str_pad(static::__data___pack([
            'version'          => $this->version,
            'lock_timestmp'    => $this->lock_timestmp,
            'meta_offset'      => $this->meta_offset,
            'meta_offset_prev' => $this->meta_offset_prev,
            'checksum'         => $this->checksum
        ]), static::HEAD_LENGTH -
            static::HEAD_TITLE_LENGTH
        ));
    }

    function __meta_save() {
        fseek ($this->stream, 0, SEEK_END);
        fwrite($this->stream, static::META_TITLE);
        $this->meta_offset = ftell($this->stream);
        if ($this->length) $this->meta_parsed[$this->path_file]['length'] = $this->length;
        if ($this->offset) $this->meta_parsed[$this->path_file]['offset'] = $this->offset;
        fwrite($this->stream, static::__data___pack($this->meta_parsed));
    }

    function __root_is_exists() {
        return $this->stream;
    }

    function __file_is_exists() {
        return $this->offset !== 0;
    }

    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦

    function stream_open($path, $mode, $options, &$opened_path) {
        $path_parsed = static::__path_parse($path);
        if ($path_parsed['path_root']) {
            $this->path_root = $path_parsed['path_root'];
            $this->path_file = $path_parsed['path_file'];
            $this->target    = $path_parsed['target'   ];
            $this->mode      = $mode ?: 'c+b';
            $mode_info = File::get_mode_info($this->mode);
            $this->mode_is_readable = $mode_info && $mode_info['is_readable'];
            $this->mode_is_writable = $mode_info && $mode_info['is_writable'];
            $this->mode_is_seekable = $mode_info && $mode_info['is_seekable'];
            if (!$this->mode_is_readable) throw new Extend_exception(File::ERR_MESSAGE_MODE_IS_NOT_READABLE, File::ERR_CODE_MODE_IS_NOT_READABLE, ['File "%%_file" cannot be created or opened or modified!', 'File mode does not support reading!'], ['file' => $this->path_root]);
            if (!$this->mode_is_seekable) throw new Extend_exception(File::ERR_MESSAGE_MODE_IS_NOT_SEEKABLE, File::ERR_CODE_MODE_IS_NOT_SEEKABLE, ['File "%%_file" cannot be created or opened or modified!', 'File mode does not support seeking!'], ['file' => $this->path_root]);
            try {
                $this->stream = @fopen($this->path_root, $this->mode, false, $this->context ?: stream_context_create([static::WRAPPER => []]));
            } catch (Exception $e) {}
            if ($this->stream) {
                $this->__head_init();
                $this->__meta_init();
                fseek($this->stream, $this->offset);
                return true;
            } else {
                $file = new File($path_parsed['path_root']);
                if ($file) {
                    $path = $file->path_get_absolute();
                    $dirs = $file->dirs_get_absolute();
                    $reason = File::get_fopen_error_reason($dirs, $path, $this->mode);
                    if ($reason === Directory::ERR_CODE_IS_NOT_EXISTS      ) throw new Extend_exception(Directory::ERR_MESSAGE_IS_NOT_EXISTS      , Directory::ERR_CODE_IS_NOT_EXISTS      , ['File "%%_file" cannot be created or opened or modified!', 'Directory "%%_directory" is not exists!'],                 ['file' => $path, 'directory' => $dirs]);
                    if ($reason === Directory::ERR_CODE_PERM_ARE_TOO_STRICT) throw new Extend_exception(Directory::ERR_MESSAGE_PERM_ARE_TOO_STRICT, Directory::ERR_CODE_PERM_ARE_TOO_STRICT, ['File "%%_file" cannot be created or opened or modified!', 'Directory permissions of "%%_directory" are too strict!'], ['file' => $path, 'directory' => $dirs]);
                    if ($reason === File::ERR_CODE_IS_NOT_EXISTS           ) throw new Extend_exception(File::ERR_MESSAGE_IS_NOT_EXISTS           , File::ERR_CODE_IS_NOT_EXISTS           , ['File "%%_file" cannot be created or opened or modified!', 'File is not exists!'],                                     ['file' => $path]);
                    if ($reason === File::ERR_CODE_IS_EXISTS               ) throw new Extend_exception(File::ERR_MESSAGE_IS_EXISTS               , File::ERR_CODE_IS_EXISTS               , ['File "%%_file" cannot be created or opened or modified!', 'File is exists!'],                                         ['file' => $path]);
                    if ($reason === File::ERR_CODE_PERM_ARE_TOO_STRICT     ) throw new Extend_exception(File::ERR_MESSAGE_PERM_ARE_TOO_STRICT     , File::ERR_CODE_PERM_ARE_TOO_STRICT     , ['File "%%_file" cannot be created or opened or modified!', 'File permissions of "%%_file" are too strict!'],           ['file' => $path]);
                                                                             throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN                 , File::ERR_CODE_UNKNOWN);
                } else                                                       throw new Extend_exception(File::ERR_MESSAGE_PATH_IS_INVALID         , File::ERR_CODE_PATH_IS_INVALID, ['File path is invalid!', 'Correct format: "%%_format"'], ['format' => self::WRAPPER.'://path_to_'.self::WRAPPER.':internal_path']); };
        }         else                                                       throw new Extend_exception(File::ERR_MESSAGE_PATH_IS_INVALID         , File::ERR_CODE_PATH_IS_INVALID, ['File path is invalid!', 'Correct format: "%%_format"'], ['format' => self::WRAPPER.'://path_to_'.self::WRAPPER.':internal_path']);
    }

    function stream_stat() {
        if ($this->target === 'root' && $this->__root_is_exists()) return fstat($this->stream);
        if ($this->target === 'file' && $this->__file_is_exists()) {
            $stat = fstat($this->stream);
            $stat['size'] = $this->meta_parsed[$this->path_file]['length'];
            return $stat;
        }
    }

    function url_stat($path, $flags = 0) {
        $opened_path = [];
        $handle = new static;
        $handle->stream_open($path, 'rb', [], $opened_path);
        $result = $handle->stream_stat();
        $handle->stream_close();
        return $result;
    }

    function stream_seek($offset, $whence = SEEK_SET) {
        if ($whence === SEEK_CUR               ) throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_seek() error: $whence = SEEK_CUR '.               'is not correctly supported by PHP!');
        if ($whence === SEEK_SET && $offset < 0) throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_seek() error: $whence = SEEK_SET + negative offset is not correctly supported by PHP!');
        if ($this->__root_is_exists()) {
            if ($this->__file_is_exists()) {
                $min = $this->offset;
                $max = $this->offset + $this->length;
                if ($whence === SEEK_SET) {$new = $min + $offset; if ($new < $min) $new = $min; if ($new > $max) $new = $max; return fseek($this->stream, $new, SEEK_SET);} # PHP always return -1
                if ($whence === SEEK_END) {$new = $max + $offset; if ($new < $min) $new = $min; if ($new > $max) $new = $max; return fseek($this->stream, $new, SEEK_SET);} # PHP always return -1
            } else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_seek() + __file_is_exists() error!');
        }     else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_seek() + __root_is_exists() error!');
    }

    function stream_read($length = 0x2000) { # built-in value from PHP
        if ($this->__root_is_exists()) {
            if ($this->target === 'root') return fread($this->stream, $length);
            if ($this->target === 'file' && $this->__file_is_exists()) {
                $debug = debug_backtrace(0, 2);
                if ($debug[1]['function'] === 'fread') $length =
                    $debug[1][  'args'  ][1]; # fix built-in value
                $min = $this->offset;
                $max = $this->offset + $this->length;
                $cur = ftell($this->stream);
                if ($cur < $min) fseek($this->stream, $min);
                if ($cur > $max) fseek($this->stream, $max);
                $cur = ftell($this->stream);
                if ($length + $cur > $max)
                    $length = $max - $cur;
                if ($length >= 1) return fread($this->stream, $length);
                else              return '';
            } else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_read() + __file_is_exists() error!');
        }     else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_read() + __root_is_exists() error!');
    }

    function stream_write($data) {
        if ($this->mode_is_writable) {
            if ($this->__root_is_exists()) {
                if ($this->target === 'root') return fwrite($this->stream, $data);
                if ($this->target === 'file') {
                    if (fstat($this->stream)['size'] === 0)
                        $this->__head_save();
                    fseek($this->stream, 0, SEEK_END);
                    if ($this->was_changed === false)
                        $this->offset = ftell($this->stream);
                    $result = fwrite($this->stream, $data);
                    if ($result) {
                        if ($this->was_changed === false) $this->length  = strlen($data);
                        if ($this->was_changed !== false) $this->length += strlen($data);
                        $this->was_changed = true;
                    }
                    return $result;
                } else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_write() in "target === file" error!');
            }     else throw new Extend_exception(File::ERR_MESSAGE_UNKNOWN, File::ERR_CODE_UNKNOWN, 'stream_write() + __root_is_exists() error!');
        }         else throw new Extend_exception(File::ERR_MESSAGE_MODE_IS_NOT_WRITABLE, File::ERR_CODE_MODE_IS_NOT_WRITABLE);
    }

    function stream_flush() {
        if ($this->__root_is_exists()) {
            return fflush($this->stream);
        }
    }

    function stream_close() {
        if ($this->__root_is_exists()) {
            if ($this->target === 'file' && $this->was_changed === true) $this->__meta_save();
            if ($this->target === 'file' && $this->was_changed === true) $this->__head_save();
            return fclose($this->stream);
        }
    }

    function dir_closedir()                           {} # is not supported
    function dir_opendir($path, $options)             {} # is not supported
    function dir_readdir()                            {} # is not supported
    function dir_rewinddir()                          {} # is not supported
    function mkdir($path, $mode, $options)            {} # is not supported
    function rename($path_from, $path_to)             {} # is not supported
    function rmdir($path, $options)                   {} # is not supported
    function stream_cast($cast_as)                    {} # is not supported
    function stream_lock($operation)                  {} # is not supported
    function stream_metadata($path, $option, $value)  {} # is not supported
    function stream_set_option($option, $arg1, $arg2) {} # is not supported
    function stream_truncate($new_size)               {} # is not supported
    function stream_tell()                            {} # is not correctly supported by PHP
    function stream_eof()                             {} # is not correctly supported by PHP

    ###########################
    ### static declarations ###
    ###########################

    static function __data___pack($data) {return    serialize     ($data) ;}
    static function __data_unpack($data) {return @unserialize(trim($data));}

    static function __path_parse($path) {
        if (strlen((string)$path)) {
            $matches = [];
            preg_match('%^'.'(?:(?<rprotocol>[a-z]{1,20})://|)'.
                               '(?<path_root>([a-zA-Z][:][^:]+)|[^:]+|)'.
                         '(?:[:](?<path_file>.+)|)'.'$%S', (string)$path, $matches);
            $rprotocol = array_key_exists('rprotocol', $matches) ? $matches['rprotocol'] : '';
            $path_root = array_key_exists('path_root', $matches) ? $matches['path_root'] : '';
            $path_file = array_key_exists('path_file', $matches) ? $matches['path_file'] : '';
            if ($path_file)
                 return ['protocol' => $rprotocol, 'path_root' => $path_root, 'path_file' => $path_file, 'target' => 'file'];
            else return ['protocol' => $rprotocol, 'path_root' => $path_root, 'path_file' => $path_file, 'target' => 'root'];
        }        return ['protocol' => ''        , 'path_root' => ''        , 'path_file' => ''        , 'target' => 'root'];
    }

    static function meta_get($path) {
        $result = null;
        $opened_path = [];
        $handle = new static;
        $handle->stream_open($path, 'rb', [], $opened_path);
        if ($handle->__root_is_exists()) {
            $meta = $handle->meta_parsed;
            if (is_array($meta) && $handle->target === 'root'                               ) $result = $meta;
            if (is_array($meta) && $handle->target === 'file' && $handle->__file_is_exists()) $result = $meta[$handle->path_file];
            $handle->stream_close();
            return $result;
        }
    }

    static function add_file($path_src, $path_dst) {
        $handle_src = fopen($path_src,  'rb');
        $handle_dst = fopen($path_dst, 'c+b');
        if ($handle_src && $handle_dst) {
            $result = 0;
            stream_set_read_buffer ($handle_dst, 0);
            stream_set_write_buffer($handle_dst, 0);
            while (strlen($c_data = fread($handle_src, 1024)))
                $result+= fwrite($handle_dst, $c_data);
            fclose($handle_src);
            fclose($handle_dst);
            return $result;
        }
    }

    static function add_from_string($data, $path) {
        if (strlen($data)) {
            if ($handle = fopen($path, 'c+b')) {
                stream_set_read_buffer ($handle, 0);
                stream_set_write_buffer($handle, 0);
                $result = fwrite($handle, $data);
                fclose($handle);
                return $result;
            }
        }
    }

    static function unlink($path) {
        $path_parsed = static::__path_parse($path);
        if ($path_parsed['target'] === 'root') @unlink($path_parsed['path_root']);
        if ($path_parsed['target'] === 'file') {
            $opened_path = [];
            $handle = new static;
            $handle->stream_open($path, 'c+b', [], $opened_path);
            if ($handle->__root_is_exists()) {
                if ($handle->__file_is_exists()) {
                    unset($handle->meta_parsed[$path_parsed['path_file']]);
                    $handle->length = 0;
                    $handle->offset = 0;
                    $handle->__meta_save();
                    $handle->__head_save(); }
                return fclose($handle->stream);
            }
        }
    }

    static function cleaning($path) {
        $meta = static::meta_get($path);
        if ($meta) {
            @unlink($path.'.tmp');
            foreach ($meta as $c_internal_path => $null)
                static::add_file($path.':'.$c_internal_path, $path.'.tmp:'.$c_internal_path);
            $result = copy($path.'.tmp', $path);
            @unlink($path.'.tmp');
            return $result;
        }
    }

}
