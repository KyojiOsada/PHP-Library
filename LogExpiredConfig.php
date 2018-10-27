<?php

class LogExpiredConfig
{

	public static $logs = array(
		# app log 1
		array(
			'path' => '/data/www/*/*/tmp/logs/*.log*',
			'regex' => array(
				'/^app_access_(\d+)\.log/',
				'/^app_error_(\d+)\.log/',
				'/^app_success_(\d+)\.log/',
				'/^app_failure_(\d+)\.log/',
				'/^app_relay_(\d+)\.log/',
			),
			## 2 weeks
			'term' => 1209600,
			'expire_date' => null,
		),
		array(
			'path' => '/data/www/*/*/tmp/logs/*.log*',
			'regex' => array(
				'/^app_mail_(\d+)\.log/',
				'/^app_debug_(\d+)\.log/',
				'/^app_bench_(\d+)\.log/',
				'/^app_count_(\d+)\.log/',
				'/^app_customer_env_(\d+)\.log/',
			),
			## 1 day
			'term' => 86400,
			'expire_date' => null,
		),

		# app log 2
		array(
			'path' => '/data/www/*/*/tmp/logs/relay/*.log*',
			'regex' => array(
				'/^(\d+)\.log/',
			),
			## 1 week
			'term' => 604800,
			'expire_date' => null,
		),
		array(
			'path' => '/data/www/*/*/tmp/logs/*/*.log*',
			'regex' => array(
				'/^(\d+)\.log/',
			),
			## 2 weeks
			'term' => 1209600,
			'expire_date' => null,
		),

		# httpd log
		array(
			'path' => '/data/log/*/*.log*',
			'regex' => array(
				'/^httpd_access_(\d+)\.log/',
				'/^httpd_error_(\d+)\.log/',
				'/^httpd_combined_(\d+)\.log/',
				'/^httpd_attacked_(\d+)\.log/',
				'/^httpd_cracked_(\d+)\.log/',
				'/^sshd_attacked_(\d+)\.log/',
				'/^sshd_cracked_(\d+)\.log/',
			),
			## 2 weeks
			'term' => 1209600,
			'expire_date' => null,
		),

		# batch log
		array(
			'path' => '/data/log/*/*.log*',
			'regex' => array(
				'/^batch_success_(\d+)\.log/',
				'/^batch_error_(\d+)\.log$',
			),
			## 1 week
			'term' => 604800,
			'expire_date' => null,
		),
	);

}
