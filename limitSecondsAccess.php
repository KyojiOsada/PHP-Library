<?php
# Definition
function limitSecondsAccess()
{
    try {
        # Init
        ## Access Timestamp Build
        $sec_usec_timestamp = microtime(true);
        list($sec_timestamp, $usec_timestamp) = explode('.', $sec_usec_timestamp);

        ## Access Limit Default Value
        ### Depends on Specifications: For Example 10
        $access_limit = 10;

        ## Roots Build
        ### Depends on Environment: For Example '/tmp'
        $tmp_root = '/tmp';
        $access_root = $tmp_root . '/access';

        ## Auth Key
        ### Depends on Specifications: For Example 'app_id'
        $auth_key = 'app_id';

        ## Response Content-Type
        ## Depends on Specifications: For Example JSON and UTF-8
        $response_content_type = 'Content-Type: application/json; charset=utf-8';

        ## Response Bodies Build
        ### Depends on Design
        $response_bodies = array();

        # Authorized Key Check
        if (empty($_REQUEST[$auth_key])) {
            throw new Exception('Unauthorized', 401);
        }
        $auth_id = $_REQUEST[$auth_key];

        # The Auth Root Build
        $auth_root = $access_root . '/' . $auth_id;

        # The Auth Root Check
        if (! is_dir($auth_root)) {
            ## The Auth Root Creation
            if (! mkdir($auth_root, 0775, true)) {
                throw new Exception('Could not create the auth root. ' . $auth_root, 500);
            }
        }

        # A Access File Creation Using Micro Timestamp
        /* For example, other data resources such as memory cache or RDB transaction.
         * In the case of this sample code, it is lightweight because it does not require file locking and transaction processing.
         * However, in the case of a cluster configuration, file system synchronization is required.
         */
        $access_file_path = $auth_root . '/' . strval($sec_usec_timestamp);
        if (! touch($access_file_path)) {
            throw new Exception('Could not create the access file. ' . $access_file_path, 500);
        }

        # The Auth Root Scanning
        if (! $base_names = scandir($auth_root)) {
            throw new Exception('Could not scan the auth root. ' . $auth_root, 500);
        }

        # The Access Counts Check
        $access_counts = 0;
        foreach ($base_names as $base_name) {
            ## A current or parent dir
            if ($base_name === '.' || $base_name === '..') {
                continue;
            }

            ## A Access File Path Build
            $file_path = $auth_root . '/' . $base_name;

            ## Not File Type
            if (! is_file($file_path)) {
                continue;
            }

            ## The Base Name to Integer Data Type
            $base_name_sec_timestamp = intval($base_name);

            ## Same Seconds Timestamp
            if ($sec_timestamp === $base_name_sec_timestamp) {
            
                ## The Base Name to Float Data Type
                $base_name_sec_usec_timestamp = floatval($base_name);

                ### A Overtaken Processing
                if ($sec_usec_timestamp < $base_name_sec_usec_timestamp) {
                    continue;
                }

                ### Access Counts Increment
                $access_counts++;

                ### Too Many Requests
                if ($access_counts > $access_limit) {
                    throw new Exception('Too Many Requests', 429);
                }

                continue;
            }

            ## Past Access Files Garbage Collection
            if ($sec_timestamp > $base_name_sec_timestamp) {
                @unlink($file_path);
            }
        }
    } catch (Exception $e) {
        # The Exception to HTTP Status Code
        $http_code = $e->getCode();
        $http_status = $e->getMessage();

        # 4xx
        if ($http_code >= 400 && $http_code <= 499) {
            # logging
            ## snip...
        # 5xx
        } else if ($http_code >= 500) {
            # logging
            ## snip...

            # The Exception Message to HTTP Status
            $http_status = 'foo';
        # Others
        } else {
            # Logging
            ## snip...

            # HTTP Status Code for The Response
            $http_status = 'Internal Server Error';
            $http_code = 500;
        }

        # Response Headers Feed
        header('HTTP/1.1 ' . $http_code . ' ' . $http_status);
        header($response_content_type);

        # A Response Body Build
        $response_bodies['message'] = $http_status;
        $response_body = json_encode($response_bodies);
        
        # The Response Body Feed
        exit($response_body);
    }
}

# Execution
limitSecondsAccess();
?>
