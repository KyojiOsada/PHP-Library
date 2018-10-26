<?php


class FooBatch {

	# Data Config
	// snip...

	# Log Config
	public $result_logs = array(
		array(
			'dir' => // Log Directory Path,
			'name' => 'batch_error_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
		array(
			'dir' => // Log Directory Path,
			'name' => 'batch_success_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
	);

	# Init
	public $host = null;
	public $env = null;
	public $timestamp = null;
	public $date = null;
	public $datetime = null;
	public $last_timestamp = null;
	public $last_date = null;
	public $last_datetime = null;


	public function __construct($_args)
	{
		try {
			# set Debug Mode
			ini_set('display_errors', 'On');

			# get Host
			$this->host = getenv('HOSTNAME');

			# begin Batch
			$this->_setLog(1, __CLASS___ . ' is began.');

			# configure
			$this->configure($_args);

			# process Main
			// snip...

			# set Log Format for Success
			$this->_setLog(1, __CLASS__ . 'is finished.');

			# put Success Log
			$this->_putLog(1);

			# mail Success
			echo $this->host . ': ' __CLASS__ . " is Completed." . "\n";

			exit;
		} catch (Exception $e) {

			# set Log Format for Error
			$this->_setLog(0, $e->__toString());

			# put History Log
			$this->_putLog(1);

			# put Error Log
			$this->_putLog(0);

			# output Std
			echo $e->__toString() . "\n";

			# mail Error
			$cmd = 'echo "' . $e->__toString() . '" | mail -s " [ ' . $this->env . " ] Foo Batch Error on " . $this->host . '" -r "sender@example.com" receiver@example.com';
			$outputs = array();
			$stauts = null;
			$result = exec($cmd , $outputs, $status);

			exit("1\n");
		}
	}


	public function configure($_args)
	{
		# set Log Format
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		# configure Result Log
		foreach ($this->result_logs as $i => $logs) {
			## Path
			$this->result_logs[$i]['path'] = $logs['dir'] . '/' . $logs['name'] . date('Ymd') . $logs['extension'];

			## Log Dir
			if (! is_dir($logs['dir'])) {
				if (! mkdir($logs['dir'], 0775, true)) {
					throw new Exception('Could not make result log dir. ' . $logs['dir'], 500);
				}
			}
		}

		# confiture Env
		switch (true) {
			## Prod
			case ...:
				break;
			## Stag
			case ...:
				break;
			## Dev
			case ...:
				break;
			## Local
			default:
				break;
		}

		# set Time Stamp
		$this->timestamp = empty($_args[1]) ? time() : strtotime($_args[1]);
		$this->date = date('Y-m-d', $this->timestamp);
		$this->datetime = date('Y-m-d H:i:s', $this->timestamp);
		$this->last_timestamp = $this->timestamp - 86400;
		$this->last_date = date('Y-m-d', $this->last_timestamp);
		$this->last_datetime = date('Y-m-d H:i:s', $this->last_timestamp);

		# set Log Format
		$this->_setLog(1, __FUNCTION__ . ' is completed.');
	}


	private function _setLog($_i, $_message)
	{
		$this->result_logs[$_i]['str'] .= date('Y-m-d H:i:s') . ' ' . $this->host . ' ' . __CLASS__ . '() ' . $_message . "\n";
	}


	private function _putLog($_i)
	{
		if (empty($this->result_logs[$_i]['str'])) {
			return null;
		}

		file_put_contents($this->result_logs[$_i]['path'], $this->result_logs[$_i]['str'], FILE_APPEND);
	}

}

new FooBatch($argv);
