<?php

class FiidClientLib
{
	public $host = '127.0.0.1';
	public $port = '50000';

	public function __construct()
	{
		error_reporting(E_ALL);

		try {
			echo 'TCP/IP Connecting' . "\n";
			echo 'Socket Opening...';
			if (false === ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
				throw new Exception('error: ' . __LINE__);
			} else {
				echo 'done' . "\n";
			}

			echo 'Connecting...';

			if (false === ($result = socket_connect($socket, $this->host, $this->port))) {
				throw new Exception('error: ' . __LINE__);
			} else {
				echo 'done' . "\n";
			}

			echo "Writing...";
			$request = 'test' . "\n";
			socket_write($socket, $request, strlen($request));
			echo 'done' . "\n";

			echo 'Read...';
			$response = '';
			$response = socket_read($socket, 2048);
			echo $response;

			echo "Writing...";
			$request = 'quit' . "\n";
			socket_write($socket, $request, strlen($request));
			echo 'done' . "\n";

		} catch (Exception $e) {
			echo $e->getMessage() . "\n";
		}

		echo 'Closing...';
		socket_close($socket);
		echo 'done' . "\n";

		exit;
	}

}
