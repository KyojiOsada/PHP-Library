<?php

class FiidServerLib
{
	# Config
	public $config_path = __DRI__ . '/FiidServerConfig.php';
	public $ipv4 = null;
	public $port = null;

	# Init
	public $Socket = null;
	public $Client = null;
	public $args = null;


	public function __construct($_args)
	{
		try {
			# PHP Setting
			error_reporting(E_ALL);
			set_time_limit(0);
			ob_implicit_flush();

			# Config
			## Config Path
			if (! is_file($this->config_path)) {
				throw new Exception('Could not find config file.', 500);
			}
			if (! is_readable($this->config_path)) {
				throw new Exception('Could not read config file.', 500);
			}
			require_once($this->config_path);

			## Config Get
			$config = new Config();
			$this->ipv4 = $config->ipv4;
			$this->port = $config->port;

			## Arguments
			$this->args = $_args;

			if ($this->args[1] === 'start') {
				$this->_start();
			} else if ($this->args[1] === 'drop') {
				$this->_drop();
			} else {
				throw new Exception('Argument is invalid.', 400);
			}
		} catch (Exception $e) {
			echo ' ' . $e->getCode() . ': ' . $e->getMessage();
			exit(1);
		}
	}


	public function _start()
	{
		$pid = getmypid();
		file_put_contents('/var/run/ssss.pid', $pid);
		try {
			# Creation
			if (false === ($this->Socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
				throw new Exception();
			}

			# Option Set
			if (! socket_set_option($this->Socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
				throw new Exception();
			}

			# Bind
			if (! socket_bind($this->Socket, $this->ipv4, $this->port)) {
				throw new Exception();
			}

			# Listen
			if (! socket_listen($this->Socket)) {
				throw new Exception();
			}

			while (true) {
				# Accept
				if (false === ($this->Client = socket_accept($this->Socket))) {
					throw new Exception('error: ' . __LINE__);
				}

				# Hello
				$hello = 'Hello!' . "\n";
				socket_write($this->Client, $hello, strlen($hello));

				while (true) {
					if (false === ($request = socket_read($this->Client, 2048, PHP_NORMAL_READ))) {
						throw new Exception('error: ' . __LINE__);
					}

					if (! $request = trim($request)) {
						continue;
					}

					if ($request === 'quit') {
						break;
					}

					if ($request === 'drop') {
						break;
					}

					$response = 'Request: ' . $request . "\n";
					socket_write($this->Client, $response, strlen($response));
					echo 'Request: ' . $request . "\n";
					echo "\n";
				}

				socket_close($this->Client);
				if ($request === 'drop') {
					break;
				}
			}

		} catch (Exception $e) {
			$code = socket_last_error($this->Socket);
			$msg = socket_strerror($code);
			echo ' ' . $code . ': ' . $msg;
			exit(1);
		}

		if (false !== $this->Socket) {
			socket_close($this->Socket);
		}
	}


	public function _drop()
	{
		try {
			if (false === ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
				throw new Exception('error: ' . __LINE__);
			}

			if (false === ($result = socket_connect($socket, $this->ipv4, $this->port))) {
				throw new Exception('error: ' . __LINE__);
			}

			$request = 'drop' . "\n";
			socket_write($socket, $request, strlen($request));

		} catch (Exception $e) {
			echo ' ' . $e->getMessage() . "\n";
			exit(1);
		}
	}

}

new FiidServer($argv);
exit(0);
