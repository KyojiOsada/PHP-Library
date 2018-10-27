<?php

class HealthCheckBatch
{

	public function __construct()
	{
		$cmd = 'ping -c 1 192.168.16.171';
		$outputs = array();
		$status = null;
		$last_line = exec($cmd, $outputs, $stauts);

		foreach ($outputs as $output) {
			if (false !== strpos($output, '1 packets transmitted, 1 received, 0% packet loss')) {
				echo 'ping success';
			}
			if (false !== strpos($output, '1 packets transmitted, 0 received, 100% packet loss')) {
				echo 'ping error';
			}
		}

		if (! $fp = fsockopen('192.168.16.171', 443, $e_code, $e_message, 5)) {
			echo 'tcp error';
		} else {
			fclose($fp);
			echo 'tcp success';
		}

		if (! $fp = fsockopen('udp://192.168.16.171', 443, $e_code, $e_message, 5)) {
			echo 'udp error';
		} else {
			fclose($fp);
			echo 'udp success';
	}

}

new HealthcheckBatch();
