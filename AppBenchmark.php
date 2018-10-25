<?php

class AppBenchmark {

	public $times = array();
	public $memories = array();
	public $count = null;
	public $output_flag = null;


	public function __construct($_output_flag = null)
	{
		$this->output_flag = $_output_flag;
	}


	public function put($_place = null)
	{
		if ($this->count === null) {
			$this->count = 0;
		}

		$this->memories[$c] = memory_get_usage();
		$this->times[$c] = microtime(true);

		$place = $_place ? $_place : $c;
		$all_time = round(time() - $_SERVER['REQUEST_TIME'], 10);
		$top_time = $this->count ? round($this->times[$this->count] - $this->times[0], 10) : '-';
		$use_time = $this->count ? round($this->times[$this->count] - $this->times[$this->count - 1], 10) : '-';
		$all_memory = number_format($this->memories[$this->count]);
		$top_memory = $this->count ? number_format($this->memories[$this->count] - $this->memories[0]) : '-';
		$use_memory = $this->count ? number_format($this->memories[$this->count] - $this->memories[$this->count - 1]) : '-';

		$output = '';
		$output .= '******** [ ' . $place . " ] ******** \n";
		$output .= 'All Time: ' . $all_time . "\n";
		$output .= 'Top Time: ' . $top_time . "\n";
		$output .= 'Use Time: ' . $use_time . "\n";
		$output .= 'All Memory: ' . $all_memory . "\n";
		$output .= 'Top Memory: ' . $top_memory . "\n";
		$output .= 'Use Memory: ' . $use_memory . "\n\n";

		if ($this->output_flag === null) {
			echo nl2br($output);
		} else {
			error_log($output, 4);
		}
		$this->count++;
    }

}
