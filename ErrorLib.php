<?php

class ErrorLib
{

    # PHP Error Numbers
    public static $error_numbers = array(
        1 => 'Fatal',
        2 => 'Warning',
        4 => 'Parse Error',
        8 => 'Notice',
        16 => 'Core Fatal',
        32 => 'Core Warning',
        64 => 'Compile Error',
        128 => 'Compile Warning',
        256 => 'Ex Error',
        512 => 'Ex Warning',
        1024 => 'Ex Notice',
        2048 => 'Strict Error',
        4096 => 'Recoverable Error',
        8192 => 'Deprecated',
        16384 => 'Ex Deprecated',
        32767 => 'All',
    );

    # HTTP Status Code
    public static $http_status_codes = array(
        'default' => 200,
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
    );


    public function __construct()
    {}


    public function handleError()
    {
        # Error Handler Definition
        function handleError($_number, $_message, $_file, $_line, $_contexts)
        {
             # Not Includ Error Reporting
             if (! (error_reporting() &amp; $_number)) {
                 return;
             }
            # to ErrorException
            throw new ErrorException($_message, 500, $_number, $_file, $_line);
        }

        # Error Handler Set
        set_error_handler('handleError');
    }


    public function handleException()
    {
        # Exception Handler Definition
        function handleException($_e)
        {
            # Exception Context
             $_SERVER['X_EXCEPTION_HANDLER_CONTEXT'] = $_e;

             # Error Processing to Shutdown Logic
             exit;
        }

        # Exception Handler Set
        set_exception_handler('handleException');
    }


    public function handleShutdown($_error_mails = array())
    {
        # Shutdown Function Definition
        function handleShutdown($_error_numbers = array(), $_error_mails = array(), $_http_status_codes = array())
        {
            # Exception or Error
            if (! empty($_SERVER['X_EXCEPTION_HANDLER_CONTEXT'])) {
                $e = $_SERVER['X_EXCEPTION_HANDLER_CONTEXT'];
                unset($_SERVER['X_EXCEPTION_HANDLER_CONTEXT']);
                $message = $e->__toString();
                $code = $e->getCode();
            } else {
                $e = error_get_last();
                # Normal Exit
                if (empty($e)) {
                    return;
                }

                # Core Error
                $message = $_error_numbers[$e['type']] . ': ' . $e['message'] . ' in ' . $e['file'] . ' on line ' . $e['line'];
                $code = 500;
            }

            # Error Logging
            error_log($message, 4);

            # Error Mail
            $cmd = 'echo "' . $message . '" | mail -S "smtp=smtp://' . $_error_mails['host'] . '" -r "' . $_error_mails['from'] . '" -s "' . $_error_mails['subject'] . '" ' . $_error_mails['to'];
            $outputs = array();
            $status = null;
            $last_line = exec($cmd, $outputs, $status);

            # HTTP Status Code
            header('HTTP/1.1 ' . $code . ' ' . $_http_status_codes[$code]);

            # Shutdown
            exit($code . ' ' . $_http_status_codes[$code]);
        }

        # Shutdown Function Registration
        $error_numbers = self::$error_numbers;
        $http_status_codes = self::$http_status_codes;
        register_shutdown_function('handleShutdown', $error_numbers, $_error_mails, $http_status_codes);
    }

}

?>
