<?php

require_once 'ZabbixApi.class.php';
use ZabbixApi\ZabbixApi;

class ZabbixReportBatch
{

	public $host = null;
	public $ZabbixApi = null;
	public $login_url = 'http://watch.example.com/api_jsonrpc.php';
	public $user = 'USER';
	public $password = 'PASSWORD';
	public $session_id = null;
	public $line_image_url = "http://watch.example.com/zabbix/chart2.php";
	public $circle_image_url = "http://watch.example.com/zabbix/chart6.php";
	public $image_width = '600';
	public $temp_dir = '/tmp';
	public $image_header_file = 'iwrr_header.png';
	public $image_header_path = null;
	public $image_start = null;
	public $image_period = null;
	public $report_date = null;
	public $report_terms = null;
	public $pdf_path = null;
	public $stamp = null;
	public $term = null;
	public $mutt_config_path = '/conf/mutt.conf';
	public $rcpt_to = 'user1@example.com user2@example.com';
	public $subject = null;
	public $zabbix_hosts = array();

	public $graph_terms = array(
		'monthly' => array(
			'image_period' => '2592000',
			'subject' => 'Monthly Report',
			'rcpt_to' => 'user1@example.com user2@example.com',
		),
		'weekly' => array(
			'image_period' => '604800',
			'subject' => 'Weekly Report',
			'rcpt_to' => 'user1@example.com user2@example.com',
		),
		'daily' => array(
			'image_period' => '86400',
			'subject' => 'Daily Report',
			'rcpt_to' => 'user1@example.com user2@example.com',
		),
	);

	public $graph_items = array(
		'monthly' => array(
			'WebServer' => array(
				'c1' => array(
					'www1.example.com' => array(
						'HTTPS access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'www2.example.com' => array(
						'HTTPS access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
				),
				'c2' => array(
					'admin1.example.com' => array(
						'HTTP access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sda3',
					),
					'admin2.example.com' => array(
						'HTTP access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sda3',
					),
					'img1.example.com' => array(
						'HTTPS access counts',
						'Network traffic on eth10',
						'Network traffic on eth11',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /data',
					),
					'img2.example.com' => array(
						'HTTPS access counts',
						'Network traffic on eth0',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /data',
					),
				),
				'b1' => array(
					'b-www1.example.com' => array(
						'HTTPS access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'b-www2.example.com' => array(
						'HTTPS access counts',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
				),
				'b2' => array(
					'b-admin1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sda3',
					),
					'b-admin2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sda3',
					),
					'b-img1.example.com' => array(
						'HTTPS access counts',
						'Network traffic on eth9',
						'Network traffic on eth10',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /data',
					),
					'b-img2.example.com' => array(
						'HTTPS access counts',
						'Network traffic on eth0',
						'Network traffic on eth1',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /data',
					),
				),
			),
			'DBServer' => array(
				'c' => array(
					'db1.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'db2.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'db3.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
				),
				'b' => array(
					'b-db1.example.com' => array(
						'MySQL queries',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'b-db2.example.com' => array(
						'MySQL queries',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'b-db3.example.com' => array(
						'MySQL queries',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
				),
				'a' => array(
					'a-db1.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'a-db2.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
					'a-db3.example.com' => array(
						'MySQL queries',
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'MySQL operations',
						'MySQL bandwidth',
						'MySQL slow queries',
						'Disk space usage /',
					),
				),
			),
			'StorageServer' => array(
				'x1' => array(
					'st1.example.com' => array(
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'st2.example.com' => array(
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'st3.example.com' => array(
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'st4.example.com' => array(
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
				),
			),
			'UtilServer' => array(
				'x1' => array(
					'dbx1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'dbx2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'b-dbx1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'b-dbx2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'a-dbx1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'a-dbx2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
				),
				'x2' => array(
					'wwwx1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'wwwx2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
				),
				'x3' => array(
					'mail1.example.com' => array(
						'Network traffic on eth9',
						'Network traffic on eth11',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
					'mail2.example.com' => array(
						'Network traffic on eth9',
						'Network traffic on eth11',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
					'watch1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'watch2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /export/sdb1',
					),
					'name1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
					'name2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
				),
				'x4' => array(
					'time1.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
					'time2.example.com' => array(
						'Network traffic on ens32',
						'Network traffic on ens33',
						'Memory usage',
						'CPU utilization',
						'CPU load',
						'Disk space usage /',
					),
				),
			),
		),
		'weekly' => array(
			// snip...
		),
		'daily' => array(
			// snip...
		),
	);

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
		# begun
		$this->setLog(1, __CLASS__ . ' is begun.');

		# set Debug Mode
		ini_set("display_errors", "On");

		try {
			$this->configure($_args);
			$this->login();
			$this->getHost();
			$this->report();

			# finished
			$this->setLog(1, __CLASS__ . ' is finishec.');

			# log Success
			$this->putLog(1);

		} catch (Exception $e) {
			$this->setLog(0, $e->__toString());

			# log History
			$this->putLog(1);

			# log Error
			$this->putLog(0);

			# mail
			echo $e->__toString() . "\n";

			exit('1');
		}
	}


	public function configure($_args)
	{
		$this->setLog(1, __FUNCTION__ . ' is started.');

		# Result Log
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

		if (empty($_args[1])) {
			throw new Exception('No term.');
		}
		$this->term = $_args[1];

		$this->stamp = time();
		switch (true) {
			case $this->term === 'yearly':
				# 6 months ago
				$halfyear_stamp = $this->stamp - 15552000;
				$this->image_start = date('Y', $halfyear_stamp) . '0101000000';
				$this->report_date = date('Y', $halfyear_stamp);
				$this->report_terms = date('Y year', $halfyear_stamp);
				break;
			case $this->term === 'monthly':
				# 20 days ago
				$lastmonth_stamp = $this->stamp - 1728000;
				$this->image_start = date('Ym', $lastmonth_stamp) . '01000000';
				$this->report_date = date('Ym', $lastmonth_stamp);
				$this->report_terms = date('Y/m', $lastmonth_stamp);
				break;
			case $this->term === 'weekly':
				# 7 days ago
				$lastweek_stamp = $this->stamp - 604800;
				# week num get
				$week_num = intval(date('w', $lastweek_stamp));
				# dayback
				$dayback_stamp = 0;
				if ($week_num > 1) {
					$week_num = $week_num - 1;
					$dayback_stamp = 86400 * $week_num;
				}
				# monday
				$monday_stamp = $lastweek_stamp - $dayback_stamp;
				$this->image_start = date('Ymd', $monday_stamp) . '000000';
				$this->report_date = date('YW', $monday_stamp);
				$start_terms = date('Y/m/d', $monday_stamp) . '(Mon) 00:00';
				$sunday_stamp = $monday_stamp + (86400 * 7);
				$end_terms = date('Y/m/d', $sunday_stamp) . '(Sun) 23:59';
				$this->report_terms = $start_terms . ' ～ ' . $end_terms;
				break;
			case $this->term === 'daily':
				# 1 day ago
				$lastday_stamp = $this->stamp - 86400;
				$this->image_start = date('Ymd', $lastday_stamp) . '000000';
				$this->report_date = date('Ymd', $lastday_stamp);
				$weeks = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
				$num = date('w', $lastday_stamp);
				$day = date('Y/m/d', $lastday_stamp);
				$this->report_terms = $day . '(' . $weeks[$num] . ');
				break;
			default:
				throw new Exception('Invalid term. ' . $this->term);
				break;
		}

		# Image Period
		if (empty($this->graph_terms[$this->term]['image_period'])) {
			throw new Exception('No property image period. ' . $this->term);
		}
		$this->image_period = $this->graph_terms[$this->term]['image_period'];

		# Mail Subject
		if (empty($this->graph_terms[$this->term]['subject'])) {
			throw new Exception('No property subject. ' . $this->term);
		}
		$this->subject = $this->graph_terms[$this->term]['subject'];

		# Report RCPTTO
		if (empty($this->graph_terms[$this->term]['rcpt_to'])) {
			throw new Exception('No property rcpt_to. ' . $this->term);
		}
		$this->rcpt_to = $this->graph_terms[$this->term]['rcpt_to'];

		# Temp Dir
		if (empty($this->temp_dir)) {
			throw new Exception('No property image dir.');
		}
		$this->temp_dir = __DIR__ . $this->temp_dir;

		# Temp Dir
		if (empty($this->image_header_file)) {
			throw new Exception('No property image header file.');
		}
		$this->image_header_path = __DIR__ . '/' . $this->image_header_file;

		# mutt Config Path
		if (empty($this->mutt_config_path)) {
			throw new Exception('No property mutt config path.');
		}
		$this->mutt_config_path = __DIR__ . $this->mutt_config_path;

		$this->setLog(1, __FUNCTION__ . ' is done.');
	}


	public function login()
	{
		$this->setLog(1, __FUNCTION__ . ' is started.');

		$this->ZabbixApi = new ZabbixApi($this->login_url, $this->user, $this->password);
		$this->session_id = $this->ZabbixApi->authToken;

		$this->setLog(1, __FUNCTION__ . ' is done.');
	}


	public function getHost()
	{
		$this->setLog(1, __FUNCTION__ . ' is started.');

		$this->zabbix_hosts = $this->ZabbixApi->hostGet();
		if (empty($this->zabbix_hosts)) {
			throw new Exception('Error: zabbix_hosts is empty.');
		}

		$this->setLog(1, __FUNCTION__ . ' is done.');
	}


	public function report()
	{
		$this->setLog(1, __FUNCTION__ . ' is started.');

		# Graph Items
		foreach ($this->graph_items[$this->term] as $group_name => $pages) {
			## Pages
			$pdfs = '';
			foreach ($pages as $page_num => $hosts) {
				$images = array($this->image_header_path);

				### Hosts
				foreach ($hosts as $host_name => $items) {

					#### get Host ID By Host Name
					$host_id = false;
					foreach ($this->zabbix_hosts as $zabbix_host) {
						if ($host_name !== $zabbix_host->host) {
							continue;
						}
						$host_id = $zabbix_host->hostid;
						break;
					}
					if ($host_id === false) {
						throw new Exception('Could not find zabbix host id by graph_items host name. ' . $host_name);
					}

					#### get Graph By Host ID
					$zabbix_graphs = $this->ZabbixApi->graphGet(array('hostids' => $host_id));
					if (empty($zabbix_graphs)) {
						throw new Exception('zabbix_graphs is empty.');
					}

					#### Graph Item
					foreach ($items as $item_name) {
						//* Graph ID Get
						$graph_id = false;
						foreach ($zabbix_graphs as $zabbix_graph) {
							if ($item_name !== $zabbix_graph->name) {
								continue;
							}
							$graph_id = $zabbix_graph->graphid;
							break;
						}
						if ($graph_id === false) {
							throw new Exception('Could not find zabbix graph id by graph_items item name. ' . $item_name . ' on ' . $host_name);
						}

						##### get Image Command
						$image_url = (0 === strpos($item_name, 'Disk space usage')) ? $this->circle_image_url : $this->line_image_url;
						$cmd = 'curl -Ss -b zbx_sessionid=' . $this->session_id . ' "' . $image_url . '?graphid=' . $graph_id . '&width=' . $this->image_width . '&stime=' . $this->image_start . '&period=' . $this->image_period . '"';

						##### start Buffering
						ob_start();
						##### get Image Command
						passthru($cmd, $return);
						if ($return) {
							throw new Exception('Could not get graph. ' . $return);
						}
						##### get Buffering
						$output = ob_get_contents();
						##### end Buffering
						ob_end_clean();

						##### put Graph Image
						$image_path = $this->temp_dir . '/' . $graph_id . '.png';
						if (! file_put_contents($image_path, $output)) {
							throw new Exception('Could not put file. ' . $image_path);
						}

						$images[] = $image_path;
					}
				}

				# create PDF
				$pdf_path = $this->temp_dir . '/' . $group_name . '_' . $this->term . '_' . $this->report_date . '_' . $page_num . '.pdf';
				$images = implode(' ', $images);
				$cmd = 'convert -append ' . $images . ' ' . $pdf_path;
				if (false === $this->_exec($cmd, $outputs, $return_var)) {
					throw new Exception('Could not exec. ' . $cmd);
				}
				$pdfs .= ' -a ' . $pdf_path;

				# delete PNG
				$cmd = 'rm -rf ' . $this->temp_dir . '/*.png';
				if (false === $this->_exec($cmd, $outputs, $return_var)) {
					throw new Exception('Could not exec. ' . $cmd);
				}
			}

			# start Report
			$group_name_ja = str_replace('Server', 'Server', $group_name);
			$subject = '【' . $this->report_date . $this->subject . '】' . $group_name_ja;
			$body = $this->report_terms;
			$cmd = 'echo ' . $body . ' | mutt -F ' . $this->mutt_config_path . ' -s ' . $subject . ' ' . $this->rcpt_to . $pdfs;
			#$cmd = 'mutt -F ' . $this->mutt_config_path . ' -s ' . $this->subject . ' ' . $this->rcpt_to . $pdfs;// . ' < ' . $body;

			if (false === $this->_exec($cmd, $outputs, $return_var)) {
				throw new Exception('Error: Could not exec. ' . $cmd);
			}

			# delete PDF
			$cmd = 'rm -rf ' . $this->temp_dir . '/*.pdf';
			if (false === $this->_exec($cmd, $outputs, $return_var)) {
				throw new Exception('Error: Could not exec. ' . $cmd);
			}
		}

		$this->setLog(1, __FUNCTION__ . ' is done.');
	}


	public function _exec($_command, &$_outputs = array(), &$_status = null)
	{
		$result = exec($_command, $_outputs, $_status);
		if ($_status) {
			return false;
		}
		return $result;
	}


	public function setLog($_i, $_message)
	{
		$this->result_logs[$_i]['str'] .= date('Y-m-d H:i:s') . ' Host: ' . $this->host . ' Message: ' . $_message . "\n";
	}


	public function putLog($_i)
	{
		if (empty($this->result_logs[$_i]['str'])) {
			return null;
		}

		file_put_contents($this->result_logs[$_i]['path'], $this->result_logs[$_i]['str'], FILE_APPEND);
	}

}

new ZabbixReportBatch($argv);
