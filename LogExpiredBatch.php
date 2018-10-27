<?php

class LogExpiredBatch
{

	# Init
	public $host = null;
	public $mount_dir = '/data';
	public $min_dir_size = 500;
	public $logs = array();

	public $result_logs = array(
		array(
			'dir' => '/data/log/batch',
			'name' => 'batch_error_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
		array(
			'dir' => '/data/log/batch',
			'name' => 'batch_success_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
	);


	public function __construct($_args)
	{
		$this->host = getenv('HOSTNAME');
		$this->min_dir_size = empty($_args[1]) ? 500 : $_args[1];

		# begun
		$this->_setLog(1, __CLASS__ . ' is begun.');

		# on Debug Mode
		ini_set("display_errors", "On");

		try {
			# configure Env
			$this->configure();

			# check Mount Dir
			$this->checkMountDir();

			# drop Expired Log File
			$this->dropExpiredLog();

			# finished
			$this->_setLog(1, __CLASS__ . ' is finished.');

			# Success Log
			$this->putLog(1);

			echo $this->host . ': ' . __CLASS__ . ' is completed.';
			exit;
		} catch (Exception $e) {
			$this->_setLog(0, $e->__toString());

			# log History
			$this->putLog(1);

			# log Error
			$this->putLog(0);

			# mail
			echo $e->__toString() . "\n";
			exit('1');
		}
	}


	public function configure()
	{
		$this->_setLog(1, __FUNCTION__ . ' is executed.');

		# configure
		$uts = time();

		# configure Result Log
		foreach ($this->result_logs as $i => $logs) {
			## Path
			$this->result_logs[$i]['path'] = $logs['dir'] . '/' . $logs['name'] . date('Ymd') . $logs['extension'];

			## Log Dir
			if (! is_dir($logs['dir'])) {
				if (! mkdir($logs['dir'], 2775, true)) {
					throw new Exception('Error: Could not make result log dir. ' . $logs['dir']);
				}
			}
		}

		# configure All Logs
		require_once __DIR__ . '/LogExpiredConfig.php';
		$this->logs = LogExpiredConfig::$logs;

		foreach ($this->logs as $i => $logs) {
			## Expire
			$expire_uts = $uts - $logs['term'];
			$this->logs[$i]['expire_date'] = date('Ymd', $expire_uts);
		}

		$this->_setLog(1, __FUNCTION__ . ' is done.');
	}


	public function checkMountDir()
	{
		$this->_setLog(1, __FUNCTION__ . ' is executed.');

		# Data Dir
		$cmd = "df -Tm $this->mount_dir";
		$df = $this->_exec($cmd);
		if (false === $df) {
			throw new Exception('Could not command df. ' . $this->cmd);
		}
		$df = preg_replace('/ {2,}/', ',', $df);
		$df = preg_replace('/ /', ',', $df);
		$dfs = explode(',', $df);
		if ($dfs[4] < $this->min_dir_size) {
			throw new Exception('Mount dir size is short. min: ' . $this->min_dir_size . ', avalable: ' . $dfs[4]);
		}
		$this->_setLog(1, __FUNCTION__ . ' is done. min: ' . $this->min_dir_size . ', size: ' . $dfs[4]);
	}


	public function dropExpiredLog()
	{
		$this->_setLog(1, __FUNCTION__ . ' is executed.');

		$expired_file = '';
		$flag = null;
		foreach ($this->logs as $i => $logs) {
			$paths = glob($logs['path']);
			if (empty($paths)) {
				$this->_setLog(1, 'Path is Empty. ' . $logs['path']);
				continue;
			}
			foreach ($paths as $path) {
				$file_name = basename($path);

				foreach ($logs['regex'] as $regex) {
					if (! preg_match($regex, $file_name, $matches)) {
						continue;
					}

					if ($matches[1] > $logs['expire_date']) {
						continue;
					}

					@unlink($path);
					$expired_file .= $path . ', ';
					$flag = true;
				}
			}
		}

		if ($flag) {
			$this->_setLog(1, __FUNCTION__ . ' is done. drop: ' . $expired_file);
		} else {
			$this->_setLog(1, __FUNCTION__ ' is empty.');
		}
	}


	public function _exec($_command, &$_outputs = array(), &$_return_var = null)
	{
		$result = exec($_command, $_outputs, $_return_var);
		if ($_return_var) {
				return false;
		}
		return $result;
	}


	public function _setLog($_i, $_message)
	{
		$this->result_logs[$_i]['str'] .= date('Y-m-d H:i:s') . ', Host: ' . $this->host . ', Batch: ' . __CLASS__ . ', Message: ' . $_message . "\n";
	}


	public function putLog($_i)
	{
		if (empty($this->result_logs[$_i]['str'])) {
			return null;
		}

		file_put_contents($this->result_logs[$_i]['path'], $this->result_logs[$_i]['str'], FILE_APPEND);
	}

}

new LogExpiredBatch($argv);
