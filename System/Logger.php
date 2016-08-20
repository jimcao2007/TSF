<?php
class Logger
{
    const LEVEL_TMP = 0;
    const LEVEL_DEBUG = 1;
    const LEVEL_INFO = 2;
    const LEVEL_WARN = 3;
    const LEVEL_ERR = 4;

    /**
     * 最近一次错误编码
     * @var int
     */
    public static $code = 0;

    /**
     * 最近一次错误消息，此信息可以抛给用户
     * @var string
     */
    public static $msg = '';

    /**
     * 扩展信息，此信息是面向程序员的
     */
    public static $msg_ext = '';
    /**
     * 是否为逻辑错误，可以不上报
     * @var bool
     */
    public static $is_logic = false;
    /**
     * 记录日志的级别设置
     * @var int		0=>tmp,1=>debug,2=>info,3=>warn,4=>err
     */
    public static $writeLogLevel = 1; //默认记录debug级别和以上的记录

    /**
     * 错误集队列
     * @var string
     */
    private static $_queue = array();

    /**
     * 错误级别对应表
     * @var array
     */
    private static $_level_map = array(
        0 => 'tmp',
        1 => 'debug',
        2 => 'info',
        3 => 'warn',
        4 => 'err',
    );

    /**
     * 日志路径
     * @var string
     */
    private static $_log_path = '/data/logs/';

    /**
     * 日志文件名
     * @var string
     */
    private static $_log_file_name = null;

    /**
     * 追加临时级别记录
     * 这里的消息只用于传送，不记录到文件
     *
     * @param int $code
     * @param string $msg
     * @return void
     */
    public static function tmp($code, $msg)
    {
        self::$code = $code;
        self::$msg = $msg;
        self::$_queue[] = array($code, $msg);
    }

    /**
     * 追加调试级别记录
     *
     * @param int $code
     * @param string $msg
     * @param string $loginName 记录错误的文件名称
     * @param int $backtrace_level 错误跟踪的层级，默认为0，小于0时不跟踪
     *
     * @return void
     */
    public static function debug($code, $msg, $log_name = '', $backtrace_level = 0)
    {
        self::err($code, $msg, $log_name, $backtrace_level, self::LEVEL_DEBUG);
    }

    /**
     * 追加普通级别记录
     *
     * @param int $code
     * @param string $msg
     * @param string $loginName 记录错误的文件名称
     * @param int $backtrace_level 错误跟踪的层级，默认为0，小于0时不跟踪
     *
     * @return void
     */
    public static function info($code, $msg, $log_name = '', $backtrace_level = 0)
    {
        self::err($code, $msg, $log_name, $backtrace_level, self::LEVEL_INFO);
    }

    /**
     * 追加警告级别记录
     *
     * @param int $code
     * @param string $msg
     * @param string $loginName 记录错误的文件名称
     * @param int $backtrace_level 错误跟踪的层级，默认为0，小于0时不跟踪
     *
     * @return void
     */
    public static function warn($code, $msg, $log_name = '', $backtrace_level = 0)
    {
        self::err($code, $msg, $log_name, $backtrace_level, self::LEVEL_WARN);
    }

    /**
     * 追加记录
     *
     * @param int $code
     * @param string $msg
     * @param string $loginName 记录错误的文件名称
     * @param int $backtrace_level 错误跟踪的层级，默认为0，小于0时不跟踪
     * @param int 错误级别 默认为最高级别
     *
     * @return bool
     */
    public static function err($code, $msg, $log_name = 'error', $backtrace_level = 0, $level = 4)
    {
        if (count(self::$_queue) > 20 )
        {
            self::flush();
        }

        //获取所在文件和行号
        $file = '';
        $line = 0;
        if ($backtrace_level >= 0)
        {
            $debug_info = debug_backtrace();
            $file = $debug_info[$backtrace_level]['file'];
            $line = $debug_info[$backtrace_level]['line'];
        }

        self::$_queue[] = array($code, $msg, $file, $line, $level, date('Y-m-d H:i:s'), $log_name);
        self::$code = $code;
        self::$msg = $msg;
        return true;
    }

    public static function errMsg($msg)
    {
        return self::err(100,$msg,'error',1);
    }

    public static function dump($content)
    {
        self::log('dump',var_export($content,true));
    }


    public static function backtrace($content)
    {
        $debug_info = debug_backtrace();
        foreach($debug_info as $info)
        {
            $file = $info['file'];
            $line = $info['line'];
            self::$_queue[] = array(0,$content, $file, $line, self::$writeLogLevel , date('Y-m-d H:i:s'), 'backtrace');
            self::$code = 0;
            self::$msg = $content;
        }
    }

    /**
     * 清空当前记录集
     *
     * @return void
     */
    public static function clear()
    {
        self::$_queue = array();
    }

    /**
     * 将错误记录集写入日志并清空当前记录集
     *
     * @return void
     */
    public static function flush()
    {
        if (empty(self::$_queue))
        {
            return ;
        }

        $tmp = array();
        $tmp_date = '';
        foreach (self::$_queue as $item)
        {
            //级别低于需要写日志的级别时忽略
            if (empty($item[4]) || $item[4] < self::$writeLogLevel)
            {
                continue;
            }

            //取日期用于保存到该日期目录
            $date = substr($item[5], 0, 10);
            if ($tmp_date == '')
            {
                $tmp_date = $date;
            }

            //这里保证日志不放到错误的日期目录中
            if ($tmp_date != $date)
            {
                foreach ($tmp as $k => $v) {
                    self::writeFile($k, $tmp_date, $v);
                    $tmp = array();
                }
                $tmp_date = $date;
            }

            $log_name = $item[6];
            if (!isset($tmp[$log_name]))
            {
                $tmp[$log_name] = '';
            }
            $level = self::$_level_map[$item[4]];
            $tmp[$log_name] = isset($tmp[$log_name]) ? $tmp[$log_name] : '';
            $tmp[$log_name] .= "{$item[5]}\t{$level}\t{$item[2]}:{$item[3]}\t{$item[0]}\t{$item[1]}\n";
        }

        //按需写入不同文件
        if ($tmp)
        {
            foreach ($tmp as $k => $v)
            {
                self::writeFile($k, $tmp_date, $v);
            }
        }

        //clear
        self::$_queue = array();
    }

    /**
     * 只用来记录日志
     *
     * @param string $log_name 日志文件名
     * @param string $content   日志内容
     *
     * @return void
     */
    public static function log($log_name, $content, $backtrace = true)
    {
        $date = date('Y-m-d H:i:s');

        if ($backtrace)
        {
            $trace = debug_backtrace();
            $content = "{$trace[0]['file']}:{$trace[0]['line']}\t{$content}";
        }

        self::writeFile($log_name, substr($date, 0, 10), $date . "\t" . $content);
    }

    /**
     * 设置写日志文件目录
     *
     * @param string $path
     *
     * @return void
     */
    public static function setLogPath($path)
    {
        self::$_log_path = $path;
    }

    public static function setLogLevel($level)
    {
        self::$writeLogLevel = $level;
    }

    /**
     * 设置日志文件
     * @params string $filename 文件log名字, 为空时清除掉这个设置
     * @return void
     */
    public function setLogFile($filename)
    {
        self::$_log_file_name = $filename;
    }

    /**
     * 写入文件
     *
     * @param string $log_name 名称
     * @param string $date 日期，用以生成该日期的目录
     * @param string $content 内容
     *
     * @return void
     */
    public static function writeFile($log_name, $date, $content)
    {
        //trigger error when not setted log path
        if (empty(self::$_log_path))
        {
            trigger_error($content);
            return ;
        }

        //check and create dir
        umask(0);
        $dir = self::$_log_path . str_replace('-', '', $date);
        if (!is_dir($dir) )
        {
            if (!is_writeable(self::$_log_path))
            {
                trigger_error('log dir (' . self::$_log_path . ') unwriteable');
                return ;
            }
            mkdir($dir, 0777);
        }

        //write file
        if ($log_name === '')
        {
            $log_name = 'default';
        }
        $file_path = $dir . '/' . $log_name . '.log';
        if (file_put_contents($file_path, $content . "\n", FILE_APPEND) === false )
        {
            trigger_error('cannot write log to file:' . $file_path);
        }

        //rename when size over 200M
        if (filesize($file_path)  > 209715200) //200M
        {
            rename($file_path, $file_path . '.' . date('His'));
        }
    }
}

//execute when process exit
register_shutdown_function(array('Logger', 'flush'));

//end of script
