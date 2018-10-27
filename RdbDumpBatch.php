<?php

class RdbDumpBatch
{

	# Database Config
	public $database_confs = array(
		'source' => 'mysql',
		'port' => '3306',
		'user' => 'USER',
		'password' => 'PASSWORD',
	);

	# Database List
	public $database_list = array(
		'db1.example.com' => 'prod',
		'db2.example.com' => 'prod',
		'db3.example.com' => 'prod',
		'b-db1.example.com' => 'stag',
		'b-db2.example.com' => 'stag',
		'b-db3.example.com' => 'stag',
		'a-db1.example.com' => 'devel',
		'a-db2.example.com' => 'devel',
		'a-db3.example.com' => 'devel',
		'xdb1.example.com' => 'test',
		'localhost' => 'test',
	);

	# Init
	public $backup_host = null;
	public $backup_num = null;
	public $database_host = null;
	public $database_num = null;
	public $database_ip = null;
	public $dump_dir = '/data/dump';
	public $dump_name = '_batch_';
	public $dump_extension = '.sql';
	public $dump_regex = '/_batch_(\d+)\.sql/';
	public $dump_path = null;
	public $dump_expire_days = null;
	public $dump_term = null;
	public $mount_dir = '/data';
	public $mount_dir_min_giga_size = null;
	public $mount_dir_min_size = null;
	public $env = null;
	public $rcpt_tos = array();

	# Result Log
	public $result_logs = array(
		array(
			'dir' => '/data/log',
			'name' => 'batch_error_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
		array(
			'dir' => '/data/log',
			'name' => 'batch_success_',
			'extension' => '.log',
			'path' => null,
			'str' => '',
		),
	);


	public function __construct($_args)
	{
		$this->backup_host = getenv('HOSTNAME');
		$this->database_host = $_args[1];
		$this->dump_expire_days = $_args[2];
		$this->mount_dir_min_giga_size = $_args[3];

		# begun
		$this->_setLog(1, __CLASS__ . ' is begun.');
		$this->startDump();

		# set Debug Mode
		ini_set("display_errors", "On");

		try {
			# configure Env
			$this->configure();

			# drop Expired Dump
			$this->dropExpiredDump();

			# check Mount Dir
			$this->checkMountDir();

			# dump Database
			$this->dumpDatabase();

			# End
			$this->_setLog(1, __CLASS__ . ' is finished.');

			# log Success
			$this->putLog(1);

			# complete Dump
			$this->completeDump();

			# compress Dump File
			$this->compressDumpFile();

			exit;
		} catch (Exception $e) {
			$this->_setLog(0, $e->__toString());

			# log Success
			$this->putLog(1);

			# log Error
			$this->putLog(0);

			# mail
			echo $e->__toString() . "\n";

			exit('1');
		}
	}


	public function startDump()
	{
		# start Date
		$this->start_stamp = time();
		$start_date = date('Y-m-d H:i:s');

		# backup Number
		$hosts = explode('.', $this->backup_host);
		if (! preg_match('/\d+/', $hosts[0], $matches)) {
			throw new Exception('The backup host is invalid. ' . $this->backup_host);
		}
		$this->backup_num = $matches[0];

		# number Database
		$hosts = explode('.', $this->database_host);
		if (! preg_match('/\d+/', $hosts[0], $matches)) {
			throw new Exception('The database host is invalid. ' . $this->database_host);
		}
		$this->database_num = $matches[0];

		# list Database Env
		if (! isset($this->database_list[$this->database_host])) {
			throw new Exception('The database host is invalid. ' . $this->database_host);
		}
		$this->env = $this->database_list[$this->database_host];

		# mail RCPT TO
		require_once __DIR__ . '/RdbDumpConfig.php';
		$this->rcpt_tos = RdbDumpConfig::$rcpt_tos;
		$rcpt_tos = implode(' ', $this->rcpt_tos);

		# start Report
		$body = 'The database backup is started.' . "\n\n" . $start_date . "\n" . 'Database: ' . $this->database_host . "\n" . 'Backup: ' . $this->backup_host;
		$cmd = 'echo -e "' . $body . '" | mail -s "【Backup Start】' . $this->env . 'DB" -r "RDB Backup' . $this->backup_num . '<back' . $this->backup_num . '@minikura.com>" ' . $rcpt_tos;
		if (false === $this->_exec($cmd)) {
			throw new Exception('Could not mail starter. ' . $cmd);
		}
	}


	public function configure()
	{
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		# check Database Host
		if (! array_key_exists($this->database_host, $this->database_list)) {
			throw new Exception('Could not match the database host list. ' . $this->database_host);
		}

		# check Database IP
		if (! $this->database_ip = gethostbyname($this->database_host)) {
			throw new Exception('Could not resolve the database host. ' . $this->database_host);
		}

		# from Dump Days to Dump Term
		if (! preg_match('/^[1-9]$|^[1-9]\.\d{1,2}$/', $this->dump_expire_days)) {
			throw new Exception('The dump days is invalid. ' . $this->dump_expire_days);
		}
		$this->dump_term = 60 * 60 * 24 * floatval($this->dump_expire_days);

		# check Mount Dir Giga Min Size to Mega Size
		if (! preg_match('/^[1-9]|[1-9]\d{1,3}$/', $this->mount_dir_min_giga_size)) {
			throw new Exception('The mount dir minimum size is invalid. ' . $this->mount_dir_min_giga_size);
		}
		$this->mount_dir_min_size = intval($this->mount_dir_min_giga_size) * 1000;

		# build Dump File Path
		$this->dump_path = $this->dump_dir . '/' . $this->database_host . $this->dump_name . date('YmdH') . $this->dump_extension;

		# from Dump Term to Dump Expire Date
		$uts = time();
		$dump_expire_stamp = $uts - $this->dump_term;
		$this->dump_expire_date = date('YmdH', $dump_expire_stamp);

		# check Dump Dir
		if (! is_dir($this->dump_dir)) {
			if (! mkdir($this->dump_dir, 2775, true)) {
				throw new Exception('Could not make the dump dir. ' . $this->dump_dir);
			}
		}

		# set Result Log
		foreach ($this->result_logs as $i => $logs) {
			## Path
			$this->result_logs[$i]['path'] = $logs['dir'] . '/' . $logs['name'] . date('Ymd') . $logs['extension'];

			## Log Dir
			if (! is_dir($logs['dir'])) {
				if (! mkdir($logs['dir'], 2775, true)) {
					throw new Exception('Error: Could not make the result log dir. ' . $logs['dir']);
				}
			}
		}

		$this->_setLog(1, __FUNCTION__ . ' is done.');
	}


	public function dropExpiredDump()
	{
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		$files = scandir($this->dump_dir);
		$expired_file = '';
		$flag = null;
		foreach ($files as $file_name) {
			if ($file_name ===  '..' || $file_name === '.') {
				continue;
			}

			if (! is_file($this->dump_dir . '/' . $file_name)) {
				continue;
			}

			if (! preg_match($this->dump_regex, $file_name, $matches)) {
				continue;
			}

			if ($matches[1] > $this->dump_expire_date) {
				continue;
			}

			@unlink($this->dump_dir . '/' . $file_name);
			$expired_file .= $file_name . ', ';
			$flag = true;
		}

		if ($flag) {
			$this->_setLog(1, __FUNCTION__ . ' is done. drop: ' . $expired_file);
		} else {
			$this->_setLog(1, __FUNCTION__ . ' is empty.');
		}
	}


	public function checkMountDir()
	{
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		# Data Dir
		$cmd = "df -Tm $this->mount_dir";
		$outputs = array();
		$status = null;
		$df = exec($cmd, $outputs, $status);
		if ($status) {
			throw new Exception('Could not command df. ' . $cmd);
		}

		$df = preg_replace('/ {2,}/', ',', $df);
		$df = preg_replace('/ /', ',', $df);
		$dfs = explode(',', $df);
		if ($dfs[4] < $this->mount_dir_min_size) {
			throw new Exception('The mount dir size is short. min: ' . $this->mount_dir_min_size . ', avalable: ' . $dfs[4]);
		}
		$this->_setLog(1, __FUNCTION__ . ' is done. min: ' . $this->mount_dir_min_size . ', size: ' . $dfs[4]);
	}


	public function dumpDatabase()
	{
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		# Warning!
		## for Slave Dump of Prod Database
		if ($this->database_host === 'db3.example.com') {
			$cmd = 'mysqldump -h ' . $this->database_ip . ' -u ' . $this->database_confs['user'] . ' --all-databases --dump-slave=2 --apply-slave-statements --include-master-host-port --routines --events --hex-blob --single-transaction --default-character-set=utf8 --order-by-primary --extended-insert --add-locks --create-options --disable-keys --quick -p' . $this->database_confs['password'] . ' > ' . $this->dump_path;
		## for Master Dump
		###! The slave dump of devel and staging database are late.
		} else {
			$cmd = 'mysqldump -h ' . $this->database_ip . ' -u ' . $this->database_confs['user'] . ' --all-databases --master-data=2 --routines --events --hex-blob --single-transaction --default-character-set=utf8 --order-by-primary --extended-insert --add-locks --create-options --disable-keys --quick -p' . $this->database_confs['password'] . ' > ' . $this->dump_path;
		}

		$start_stamp = time();

		if (false === $this->_exec($cmd)) {
			$cmd = str_replace(array('-u ', $this->database_confs['user'], '-p', $this->database_confs['user']), '', $cmd);
			throw new Exception('Could not dump the database. ' . $cmd);
		}
		$end_stamp = time();
		$stamp = $end_stamp - $start_stamp;
		$time = gmdate('H:i:s', $stamp);

		$this->_setLog(1, __FUNCTION__ . ' is done. time: ' . $time);
	}


	public function completeDump()
	{
		$stamp = time() - $this->start_stamp;
		$time = gmdate('H:i:s', $stamp);
		$size = filesize($this->dump_path) / 1048576;
		$size = number_format(round($size, 3), 3);

		$body = 'The database backup is comopleted.' . "\n\n";
		$body .= date('Y-m-d H:i:s') . "\n";
		$body .= 'Database: ' . $this->database_host . "\n";
		$body .= 'Backup: ' . $this->backup_host . "\n";
		$body .= 'Size: ' . $size . ' MB' . "\n";
		$body .= 'Time: ' . $time . "\n";

		$rcpt_tos = implode(' ', $this->rcpt_tos);

		# report Completion
		$cmd = 'echo -e "' . $body . '" | mail -s "【Backup Comleted】' . $this->env . 'DB" -r "RDB Backup' . $this->backup_num . '<back' . $this->backup_num . '@minikura.com>" ' . $rcpt_tos;
		if (false === $this->_exec($cmd)) {
			throw new Exception('Could not mail dump report. ' . $cmd);
		}
	}


	public function compressDumpFile()
	{
		$this->_setLog(1, __FUNCTION__ . ' is started.');

		# set Start Time
		$comp_start_stamp = time();

		# check Dump File
		if (! is_file($this->dump_path)) {
			$this->_setLog(1, 'The dump file does not exists.');
			return;
		}

		# compress Dump File
		$cmd = 'tar -zcvf ' . $this->dump_path . '.tar.gz -C ' . $this->dump_dir . ' ' . basename($this->dump_path);
		$outputs = array();
		$status = null;
		$last_line = exec($cmd, $outputs, $status);
		if ($status) {
			throw new Exception('Could not compress the dump file. ' . $this->dump_path, 500);
		}

		# check Compression File
		if (! is_file($this->dump_path . '.tar.gz')) {
			$this->_setLog(1, 'The compression file does not exists.');
			return;
		}

		# report Compressed
		$comp_stamp = time() - $comp_start_stamp;
		$comp_time = gmdate('H:i:s', $comp_stamp);
		$dump_size = filesize($this->dump_path) / 1048576;
		$dump_size = number_format(round($dump_size, 3), 3);
		$comp_size = filesize($this->dump_path . '.tar.gz') / 1048576;
		$comp_size = number_format(round($comp_size, 3), 3);

		$body = 'The dump file compression is comopleted.' . "\n\n";
		$body .= date('Y-m-d H:i:s') . "\n";
		$body .= 'Database: ' . $this->database_host . "\n";
		$body .= 'Backup: ' . $this->backup_host . "\n";
		$body .= 'Dump Size: ' . $dump_size . ' MB' . "\n";
		$body .= 'Comp Size: ' . $comp_size . ' MB' . "\n";
		$body .= 'Time: ' . $comp_time . "\n";

		$rcpt_tos = implode(' ', $this->rcpt_tos);

		$cmd = 'echo -e "' . $body . '" | mail -s "【Dump Comp Completed】' . $this->env . 'DB" -r "RDB Backup' . $this->backup_num . '<back' . $this->backup_num . '@minikura.com>" ' . $rcpt_tos;
		if (false === $this->_exec($cmd)) {
			throw new Exception('Could not mail comp report. ' . $cmd);
		}

		# remove Source Dump File
		@unlink($this->dump_path);

		$this->_setLog(1, __FUNCTION__ . ' is done.');
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
		$this->result_logs[$_i]['str'] .= date('Y-m-d H:i:s') . ' Host: ' . $this->backup_host . ' to ' . $this->database_host . ': ' . $_message . "\n";
	}


	public function putLog($_i)
	{
		if (empty($this->result_logs[$_i]['str'])) {
			return null;
		}

		file_put_contents($this->result_logs[$_i]['path'], $this->result_logs[$_i]['str'], FILE_APPEND);
	}

}

new RdbDumpBatch($argv);
