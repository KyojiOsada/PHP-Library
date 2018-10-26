<?php

class GitDeployment
{

	public $users = array(
		"user-1" => "user-1@example.com",
		"user-2" => "user-2@example.com",
	);

	public $admins = array(
		"admin-1" => "admin-1@example.com",
	);

	public $mailing_lists = array(
		"ml@example.com",
	);

	public $prod_confs = array(
		0 => array(
			"host" => "HOST-1",
			"ip" => "192.168.0.1",
			"port" => "22",
			"user" => "USER",
			"public" => "/PATH-TO-PUBLIC-KEY/id_rsa.pub",
			"private" => "/PATH-TO-PRIVEATE-KEY/id_rsa",
			"sudo_pw" => "PASSWORD",
		),
		1 => array(
			"host" => "HOST-2",
			"ip" => "192.168.0.2",
			"port" => "22",
			"user" => "USER",
			"public" => "/PATH-TO-PUBLIC-KEY/id_rsa.pub",
			"private" => "/PATH-TO-PRIVEATE-KEY/id_rsa",
			"sudo_pw" => "PASSWORD",
		),
	);

	public $staging_confs = array(
		0 => array(
			"host" => "HOST-3",
			"ip" => "192.168.0.3",
			"port" => "22",
			"user" => "USER",
			"public" => "/PATH-TO-PUBLIC-KEY/id_rsa.pub",
			"private" => "/PATH-TO-PRIVEATE-KEY/id_rsa",
			"sudo_pw" => "PASSWORD",
		),
		1 => array(
			"host" => "HOST-4",
			"ip" => "192.168.0.4",
			"port" => "22",
			"user" => "USER",
			"public" => "/PATH-TO-PUBLIC-KEY/id_rsa.pub",
			"private" => "/PATH-TO-PRIVEATE-KEY/id_rsa",
			"sudo_pw" => "PASSWORD",
		),
	);

	public $smtp_confs = array(
		"PROTOCOL" => "smtp",
		"HOST" => "192.168.0.5",
		"PORT" => 25,
		"MAIL FROM" => "sender@example.com",
	);


	public function __construct()
	{}


	public function checkUserArgument($_args)
	{
		# User Name Args Check
		echo "user name argument checking... ";

		if (! isset($_args[1])) {
			$this->color("error", 31);
			throw new Exception('Empty Username', 1);
		}

		if (! array_key_exists($_args[1], $this->users)) {
			$this->color("error", 31);
			throw new Exception('Unknown Username', 1);
		}

		$this->color($_args[1], 32);

		return $_args[1];
	}


	public function checkRemoteBranchArgument($_args)
	{
		# Remote Branch Name Args Check
		echo "remote branch argument checking... ";

		if (! isset($_args[2])) {
			$this->color("error", 31);
			throw new Exception('Empty Remote Branch', 1);
		}

		$this->color($_args[2], 32);

		return $_args[2];
	}


	public function checkLocalBranchArgument($_args, $_remote_branch)
	{
		# Local Branch Name Args Check
		echo "local branch argument checking... ";

		$_args[3] = isset($_args[3]) ? $_args[3] : $_remote_branch;
		$this->color($_args[3], 32);

		return $_args[3];
	}


	public function checkMailArgument($_args)
	{
		# Mail Args Check
		echo "mail argument checking... ";

		$_args[4] = isset($_args[4]) ? $_args[4] : true;
		$this->color($_args[4], 32);

		return $_args[4];
	}


	public function exec($_command, &$_outputs = array(), &$_return_var = null)
	{
		$result = exec($_command, $_outputs, $_return_var);

		if ($_return_var) {
			return false;
		}

		return $result;
	}


	public function confirm($_params)
	{
		$count = isset($_params["count"]) ? $_params["count"] : 5;
		$c = 0;
		while (true) {
			$this->color(' ' . $_params['question'], 44);
			$input = trim(fgets(STDIN, 10));
			echo "Your input... ";
			$this->color($input, 32);

			if (in_array($input, $_params["stops"])) {
				throw new Exception('User: Stop ' . $_params["action"], $_params["codes"]["stop"]);
			} else if (in_array($input, $_params["starts"])) {
				echo $_params["action"] . "... ";
				$this->color("start", 32);
				break;
			} else {
				$c++;
				if ($c === $count) {
					$this->color("Abort!", 31);
					throw new Exception('Invalid input at ' . $_params["action"], $_params["codes"]["abort"]);
				}
				$this->color("Invalid input, One more input!", 31);
				continue;
			}
		}
		return $input;
	}


	public function color($_string, $_color)
	{
		$stdout = 'echo -e "\e[' . $_color . 'm' . $_string . '\e[m"';
		echo `$stdout`;
	}


	public function cd($_path)
	{
		echo "directory changing...";
		$cmd = "cd " . $_path;
		if (false === $this->exec($cmd)) {
			$this->color(' ' . $_path, 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color(' ' . $_path, 32);
	}


	public function reserve($_lock_path, $_user, $_project, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you reserve deployment for " . $_project . " ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "reserve deployment",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Lock
		echo "reserve lock file checking... ";
		if (is_file($_lock_path)) {
			if (! is_readable($_lock_path)) {
				$this->color("error", 31);
				throw new Exception('Could not read reserve lock file.', 1);
			}

			if (! $reserver = file_get_contents($_lock_path)) {
				$this->color("error", 31);
				throw new Exception('Could not get reserve lock file contents.', 1);
			}

			if ($reserver !== $_user) {
				$this->color("error", 31);
				throw new Exception('Could not reserve. Now ' . $reserver . ' has reseved.', 1);
			}

			$this->color($_user . " has reserved.", 32);
		# Reserve Lock
		} else {
			$this->color("done", 32);
			echo "reserve lock... ";

			if (! file_put_contents($_lock_path, $_user)) {
				$this->color("error", 31);
				throw new Exception('Could not creatd reserve lock file.', 1);
			}

			$this->color("done", 32);
		}
	}


	public function unreserve($_lock_path, $_user, $_project, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you unreserve deployment for " . $_project . " ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "unreserve deployment",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Lock File Check
		echo "reserve lock file checking... ";
		$flag = false;
		if (is_file($_lock_path)) {
			if (! is_readable($_lock_path)) {
				$this->color("error", 31);
				throw new Exception('Could not read reserve lock file.', 1);
			}

			if (! $reserver = file_get_contents($_lock_path)) {
				$this->color("error", 31);
				throw new Exception('Could not get reserve lock file contents.', 1);
			}

			if ($reserver !== $_user) {
				$this->color("error", 31);
				throw new Exception('Could not reserve. Now ' . $reserver . ' has reseved.', 1);
			}

			$this->color("done", 32);
			echo "reserve lock file deleting... ";
			if (! unlink($_lock_path)) {
				$this->color("error", 31);
				throw new Exception('Could not delete reserve lock file.', 1);
			}

			$this->color("done", 32);
		# Reserve Lock
		} else {
			$this->color("no reserve", 32);
		}
	}


	public function fetchGit($_option = "")
	{
		# Fetch
		$cmd = "git fetch " . $_option;
		echo $cmd . "\n";

		if (false === $this->exec($cmd)) {
			throw new Exception('Could not ' . $cmd, 1);
		}
	}


	public function checkRemoteBranch($_remote_branch, $_remote_repo = "origin")
	{
		# Remote Branch Check
		echo "remote branch checking... ";

		# Fetch
		$cmd = "git fetch --prune";

		if (false === $this->exec($cmd)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		# Branch -r
		$cmd = "git branch -r";
		$outputs = array();

		if (false === exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$remote = "  " . $_remote_repo . "/" . $_remote_branch;

		if (! in_array($remote, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Unknown Remote Branch (' . $_remote_branch . ')', 1);
		}

		$this->color($_remote_branch, 32);
	}


	public function checkLocalBranch($_local_branch, $_remote_branch, $_remote_repo = "origin")
	{
		# Local Branch Check
		echo "local branch checking... ";

		$cmd = "git branch";
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$locals = array("* " . $_local_branch, "  " . $_local_branch);

		# Exists
		if (in_array($locals[0], $outputs) || in_array($locals[1], $outputs)) {
			$this->color($_local_branch, 32);
		# Nothing
		} else {
			## Confirm
			$params = array();
			$params = array(
				"question" => "Do you create local branch: " . $_local_branch . " from remote branch: " . $_remote_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "create local branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);

			## Create
			echo "create local " . $_local_branch . " branch...";
			$cmd = "git branch " . $_local_branch . " " . $_remote_repo . "/" . $_remote_branch;
			echo $cmd . "\n";

			if (false === $this->exec($cmd)) {
				$this->color("error", 31);
				throw new Exception('Could not ' . $cmd, 1);
			}

			$this->color("done", 32);
		}
	}


	public function lookBranchName($_local_branch, $_remote_branch)
	{
		$this->color("-- look branch name --", 32);
		$this->color("Local: " . $_local_branch, 32);
		$this->color("Remote: " . $_remote_branch, 32);
	}


	public function checkDiff($_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you check diff from REMOTE: " . $_remote_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "check diff",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "diff check... ";

		# Fetch
		$cmd = "git fetch " . $_remote_repo;

		if (false === $this->exec($cmd)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		# Log 1 row
		$cmd = "git log " . $_remote_repo . "/" . $_remote_branch . " --stat -1";
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);

		foreach ($outputs as $output) {
			echo $output . "\n";
		}
	}


	public function getCommitId($_remote_branch, $_remote_repo = "origin")
	{
		# Commit ID
		$cmd = "git log " . $_remote_repo . "/" . $_remote_branch . " --stat -1 --format=%H";
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			throw new Exception('Could not ' . $cmd, 1);
		}

		return $outputs[0];
	}


	public function mergeRemoteToLocal($_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you merge branch from remote: " . $_remote_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "merge remote to local",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "merge remote to local... ";

		# Fetch Prune
		$cmd = "git fetch";
		if (false === $this->exec($cmd)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		# Merge
		Remerge:
		$reset_count = 0;
		$cmd = "git merge " . $_remote_repo . "/" . $_remote_branch;
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$reset = null;
		foreach ($outputs as $output) {
			echo $output . "\n";
			## Conflict
			if (false !== strpos($output, "CONFLICT")) {
				$this->color("error", 31);
				throw new Exception("Merge conflict!", 1);
			}

			## Reset
			if (false !== strpos($output, "error: ")) {
				$cmd = "git reset --hard";
				echo $cmd . "\n";
				if (false === $this->exec($cmd)) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}
				$reset_count++;
				if ($reset_count === 3) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}
				goto Remerge;
			}
		}

		$this->color("done", 32);
	}


	public function mergeLocalToLocal($_source_branch, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you merge branch from local: " . $_source_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "merge local to local",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "merge local to local... ";

		# Merge
		Remerge:
		$reset_count = 0;
		$cmd = "git merge " . $_source_branch;
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$reset = null;
		foreach ($outputs as $output) {
			echo $output . "\n";

			## Conflict
			if (false !== strpos($output, "CONFLICT")) {
				$this->color("error", 31);
				throw new Exception("Merge conflict!", 1);
			}

			## Reset
			if (false !== strpos($output, "error: ")) {
				$cmd = "git reset --hard";
				echo $cmd . "\n";
				if (false === $this->exec($cmd)) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}

				$reset_count++;
				if ($reset_count === 3) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}

				goto Remerge;
			}
		}

		$this->color("done", 32);
	}


	public function mergeLocalToRemote($_local_branch, $_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you merge from REMOTE: " . $_remote_branch . " into LOCAL: " . $_local_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "merge",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "git push " . $_local_branch . " to " . $_remote_repo . "/" . $_remote_branch . "... ";

		# Merge
		Remerge:
		$reset_count = 0;
		$cmd = "git push " . $_remote_repo . " " . $_local_branch . ":" . $_remote_branch;
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$reset = null;
		foreach ($outputs as $output) {
			echo $output . "\n";
			## Conflict
			if (false !== strpos($output, "CONFLICT")) {
				$this->color("error", 31);
				throw new Exception("Merge conflict!", 1);
			}

			## Reset
			if (false !== strpos($output, "error: ")) {
				$cmd = "git reset --hard";
				echo $cmd . "\n";
				if (false === $this->exec($cmd)) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}
				$reset_count++;
				if ($reset_count === 3) {
					$this->color("error", 31);
					throw new Exception('Could not ' . $cmd, 1);
				}
				goto Remerge;
			}
		}

		$this->color("done", 32);
	}


	public function deleteRemoteBranch($_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you delete remote " . $_remote_branch . " branch ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "delete remote branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Search
		echo "remote " . $_remote_branch . " branch search... ";
		$cmd = "git branch -r";
		$outputs = array();
		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new exception('Could not ' . $cmd, 1);
		}

		$branch = "  " . $_remote_repo . "/" . $_remote_branch;
		if (! in_array($branch, $outputs)) {
			$this->color("none", 32);
			return false;
		}

		$this->color("finded", 32);

		# Delete
		echo "delete remote " . $_remote_branch . " branch... ";
		$cmd = "git push --delete " . $_remote_repo . " " . $_remote_branch;
		$outputs = array();

		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function deleteLocalBranch($_local_branch, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you delete local " . $_local_branch . " branch ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "delete local branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Search
		echo "local " . $_local_branch . " branch searching... ";
		$cmd = "git branch";

		$outputs = array();
		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new exception('Could not ' . $cmd, 1);
		}

		$branch_1 = "*  " . $_local_branch;

		if (in_array($branch_1, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not delete local ' . $_local_branch . ' branch, because current branch.', 1);
		}

		$branch_2 = "  " . $_local_branch;
		if (! in_array($branch_2, $outputs)) {
			$this->color("none", 32);
			return false;
		}
			$this->color("finded", 32);

		# Delete
		echo "delete local " . $_local_branch . " branch... ";
		$cmd = "git branch -D " . $_local_branch;

		$outputs = array();
		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function createLocalBranch($_source_branch, $_dist_branch, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you create local " . $_dist_branch . " branch from local " . $_source_branch . " branch ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "create local branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Create
		echo "create local " . $_dist_branch . " branch from local " . $_source_branch . " branch... ";
		$cmd = "git branch " . $_dist_branch . " " . $_source_branch;

		$outputs = array();
		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function checkoutBranch($_branch, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you checkout " . $_branch . " ? [Y / n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "checkout branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Fetch
		$cmd = "git fetch";
		echo $cmd . "... ";

		if (false === $this->exec($cmd)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);

		# Checkout
		echo "checkout " . $_branch . " branch... ";
		$cmd = "git checkout -f " . $_branch;

		$outputs = array();
		if (false === $this->exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function chown($_paths, $_own_group)
	{
		if (is_array($_paths)) {
			$path = "";
			foreach ($_paths as $_path) {
				$path .= " " . $_path;
			}
		} else {
			$path = " " . $_paths;
		}

		$cmd = "chown -R " . $_own_group . $path;
		echo $cmd . " ... ";
		exec($cmd, $outputs, $return_var);

		if ($return_var) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function findDirChmod($_path, $_mode, $_not_paths = array())
	{
		$not_path = "";
		if (! empty($_not_paths)) {
			foreach ($_not_paths as $_not_path) {
				$not_path .= " -not -path '" . $_not_path . "*'";
			}
		}

		$cmd = "find " . $_path . $not_path . " -type d -print0 | xargs -0 chmod " . $_mode;
		echo $cmd . " ... ";
		exec($cmd, $outputs, $return_var);

		if ($return_var) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function findFileChmod($_path, $_mode, $_not_paths = array(), $_not_names = array())
	{
		$not_path = "";
		if (! empty($_not_paths)) {
			foreach ($_not_paths as $_not_path) {
				$not_path .= " -not -path '" . $_not_path . "*'";
			}
		}

		$not_name = "";
		if (! empty($_not_names)) {
			foreach ($_not_names as $_not_name) {
				$not_name .= " -not -name '" . $_not_name . "'";
			}
		}

		$cmd = "find " . $_path . "/" . $not_path . $not_name . " -type f -print0 | xargs -0 chmod " . $_mode;
		echo $cmd . " ... ";
		exec($cmd, $outputs, $return_var);

		if ($return_var) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function chmod($_paths, $_mode)
	{
		if (is_array($_paths)) {
			$path = "";
			foreach ($_paths as $_path) {
				$path .= " " . $_path;
			}
		} else {
			$path = " " . $_paths;
		}

		$cmd = "chmod " . $_mode . $path;
		echo $cmd . " ... ";
		exec($cmd, $outputs, $return_var);

		if ($return_var) {
			$this->color("error", 31);
			throw new exception('could not ' . $cmd, 1);
		}

		$this->color("done", 32);
	}


	public function checkRemoteVersionBranch($_remote_repo = "origin", $_confirm = true)
	{
		# Remote Version Branch Check
		echo "remote version branch checking... ";
	
		# Fetch
		$cmd = "git fetch";
		if (false === $this->exec($cmd)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}
	
		# Branch -r
		$cmd = "git branch -r";
		$outputs = array();
		if (false === exec($cmd, $outputs)) {
			$this->color("error", 31);
			throw new Exception('Could not ' . $cmd, 1);
		}

		$this->color("done", 32);

		# Version Branches Get
		$this->color("Version Branch List", 32);
		$regex = "{^  " . $_remote_repo . "/[\d]+?\.[\d]+?\.[\d]+?$}";

		$versions = array();
		foreach ($outputs as $output) {
			if (! preg_match($regex, $output, $matches)) {
				continue;
			}
			echo $output . "\n";
			$versions[] = str_replace('  ' . $_remote_repo . '/', '', $matches[0]);
		}

		echo "max version... ";

		# No Version Branch
		if (empty($versions)) {
			$version_branch = "0.0.0";
			$this->color($version_branch, 32);
			return $version_branch;
		}

		# Max Version Get
		$prev_pad = 0;
		$major = 0;
		$minor = 0;
		$revision = 0;
		$max_version = null;
		foreach ($versions as $i => $version) {
			$levels = explode('.', $version);
			$moj = $levels[0];
			$min = str_pad($levels[1], 4, "0", STR_PAD_LEFT);
			$rev = str_pad($levels[2], 4, "0", STR_PAD_LEFT);
			$pad = intval($moj . $min . $rev);
			if ($prev_pad > $pad) {
				continue;
			}
			$prev_pad = $pad;
			$major = intval($levels[0]);
			$minor = intval($levels[1]);
			$revision = intval($levels[2]);
			$max_version = $version;
		}
		$this->color($max_version, 32);

		# Version Up Class Question
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Which version class do you raise ? [MAJOR or MINOR or REVISION / n] : ",
				"stops" => array("n"),
				"starts" => array("MAJOR", "MINOR", "REVISION"),
				"action" => "create local branch",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$input = $this->confirm($params);
		}

		echo "version auto calculation... ";
		# Up Version Calc
		if ($input === "MAJOR") {
			$major++;
			$minor = 0;
			$revision = 0;
		} else if ($input === "MINOR") {
			$minor++;
			$revision = 0;
		} else {
			$revision++;
		}

		$version_branch = $major . "." . $minor . "." . $revision;

		$this->color($version_branch, 32);

		return $version_branch;
	}


	public function connectBySsh($_host, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you connect " . $_host['host'] . " via SSH ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "ssh connect",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Connection
		echo "SSH connecting... ";
		if (! $ssh_conn = ssh2_connect($_host['ip'], $_host['port'])) {
			$this->color("error", 31);
			throw new Exception('Internal: Could not ssh connect.', 1);
		}

		$this->color(" " . $_host["host"] . " ", 42);

		# Auth Login
		echo "SSH auth login... ";
		if (! ssh2_auth_pubkey_file($ssh_conn, $_host['user'], $_host['public'], $_host['private'])) {
			$this->color("error", 31);
			throw new Exception('Internal: Could not ssh auth login.', 1);
		}

		$this->color("done", 32);

		# Stream Make
		echo "SSH stream making... ";
		if (! $stream = ssh2_shell($ssh_conn, 'xterm', null, 1000)) {
			$this->color("error", 31);
			throw new Exception('Internal: Could not make ssh stream.', 11);
		}

		$this->color("done", 32);

		# sudo
		echo "sudo ... ";
		$cmd = "sudo su\n";
		fwrite($stream, $cmd);
		sleep(1);
		stream_set_blocking($stream, false);

		$pw_flag = false;
		while ($line = fgets($stream)) {
			if (preg_match('/password for /', $line)) {
				$pw_flag = true;
				break;
			}
		}

		# sudo password
		if ($pw_flag) {
			$cmd = $_host["sudo_pw"] . "\n";
			fwrite($stream, $cmd);
			sleep(1);
			stream_set_blocking($stream, false);
		}

		# who check
		$cmd = "whoami\n";
		fwrite($stream, $cmd);
		sleep(1);
		stream_set_blocking($stream, false);

		$who_flag = false;
		while ($line = fgets($stream)) {
			if (! preg_match('/root/', $line)) {
				continue;
			}
			$who_flag = true;
			break;
		}

		if (! $who_flag) {
			$this->color("failed", 31);
			throw new Exception('Su Failed', 11);
		}
		$this->color("done", 32);

		return $stream;
	}


	public function disconnectBySsh(&$_stream, $_prod_host)
	{
		echo $_prod_host . " SSH stream closing... ";

		fclose($_stream);
		$this->color("done", 32);
	}


	public function cdBySsh(&$_stream, $_path)
	{
		# Change Dir
		echo "directory changing... ";

		$cmd = "cd " . $_path . "\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);

		while ($line = fgets($_stream)) {
			if (false !== strpos("-bash: ", $line)) {
				$this->color("error", 31);
				throw new Exception('Could not ' . trim($cmd), 11);
			}
		}
		$this->color($_path, 32);
	}


	public function closePortBySsh($_stream, $_ip, $_prod_host, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you close posrt(80, 443) at " . $_prod_host . " via SSH ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "close port",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "close port 80,443, wait 15 seconds";
		$cmd = "iptables -A INPUT -s " . $_ip . " -p tcp --dport 80 -j ACCEPT\n";
		fwrite($_stream, $cmd);
		$this->wait(1);
		stream_set_blocking($_stream, false);

		$cmd = "iptables -A INPUT -s " . $_ip . " -p tcp --dport 443 -j ACCEPT\n";
		fwrite($_stream, $cmd);
		$this->wait(1);
		stream_set_blocking($_stream, false);

		$cmd = "iptables -A INPUT -p tcp --dport 80 -j DROP\n";
		fwrite($_stream, $cmd);
		$this->wait(1);
		stream_set_blocking($_stream, false);

		$cmd = "iptables -A INPUT -p tcp --dport 443 -j DROP\n";
		fwrite($_stream, $cmd);
		$this->wait(1);
		stream_set_blocking($_stream, false);

		$this->wait(11);
		$this->color("done", 32);
	}


	public function openPortBySsh($_stream, $_prod_host, $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you open posrt(80, 443) at " . $_prod_host . " via SSH ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "open port",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "open port 80,443, wait 10 seconds";
		$cmd = "iptables -F\n";
		fwrite($_stream, $cmd);
		$this->wait(1);
		stream_set_blocking($_stream, false);

		$this->wait(9);
		$this->color("done", 32);
	}


	public function wait($_seconds = 3)
	{
		for ($i = 0; $i < $_seconds; $i++) {
			sleep(1);
			echo ".";
		}
	}


	public function checkRemoteBranchBySsh(&$_stream, $_remote_branch, $_remote_repo = "origin")
	{
		# Remote Branch Check
		echo "remote branch checking, wait 15 seconds";

		$cmd = "git fetch --prune\n";
		fwrite($_stream, $cmd);
		$this->wait(12);
		stream_set_blocking($_stream, false);

		$cmd = "git branch -r\n";
		fwrite($_stream, $cmd);
		$this->wait(3);
		stream_set_blocking($_stream, false);
		$flag = false;
		$regex = "{.+" . $_remote_repo . "\/" . $_remote_branch . "[^\d\w\/\-]+}";

		while ($line = fgets($_stream)) {
			if (! preg_match($regex, $line)) {
				continue;
			}
			$flag = true;
			break;
		}

		if (! $flag) {
			$this->color(" error", 31);
			throw new Exception('User: Unknown Remote Branch (' . $_remote_branch . ')', 11);
		}

		$this->color(" done", 32);
	}


	public function checkLocalBranchBySsh(&$_stream, $_local_branch, $_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Local Branch Check
		echo "local branch checking... ";

		$cmd = "git branch\n";
		fwrite($_stream, $cmd);
		sleep(2);
		stream_set_blocking($_stream, false);
		$regex_1 = "{.+?" . $_local_branch . "[^\d\w\/\-]+}";
		$regex_2 = "{\*.+?" . $_local_branch . "[^\d\w\/\-]+}";

		$flag = false;
		while ($line = fgets($_stream)) {
			if (! preg_match($regex_1, $line) && ! preg_match($regex_2, $line)) {
				continue;
			}
			$flag = true;
			break;
		}

		if ($flag) {
			$this->color("done", 32);
		}

		# Local Branch Create
		if (! $flag) {
			## Confirm
			if ($_confirm) {
				$params = array();
				$params = array(
					"question" => "Do you create branch local : " . $_local_branch . " from remote: ".$_remote_branch." ? [Y / n] : ",
					"stops" => array("n"),
					"starts" => array("Y"),
					"action" => "create prod local branch",
					"codes" => array("stop" => 0, "abort" => 1),
				);
				$this->confirm($params);
			}

			## Create
			echo "create prod branch local: " . $_local_branch . " from remote " . $_remote_branch . ", wait 10 seconds";
			$cmd = "git branch " . $_local_branch . " origin/" . $_remote_branch;
			fwrite($_stream, $cmd);
			$this->wait(10);
			stream_set_blocking($_stream, false);
			$this->color("done", 32);
		}
	}


	public function checkoutBranchBySsh(&$_stream, $_branch)
	{
		echo "checkout " . $_branch . ", wait 3 secounds";

		$cmd = "git checkout -f " . $_branch . "\n";
		fwrite($_stream, $cmd);
		$this->wait(3);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
	}


	public function checkDiffBySsh(&$_stream, $_remote_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you check diff from " . $_remote_branch . " ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "check ",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		# Fetch
		echo "diff check, wait 5 seconds";
		$cmd = "git fetch " . $_remote_repo . "\n";
		fwrite($_stream, $cmd);
		$this->wait(5);

		# Log
		$cmd = "git log " . $_remote_repo . "/" . $_remote_branch . " --stat -1\n";
		fwrite($_stream, $cmd);
		$this->wait(2);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
		while ($line = fgets($_stream)) {
			echo $line;
		}
	}


	public function getCommitIdBySsh(&$_stream, $_remote_branch, $_remote_repo)
	{
		$cmd = "git log " . $_remote_repo . "/" . $_remote_branch . " --stat -1 --format=%H\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);
		$c = 0;
		while ($line = fgets($_stream)) {
			if ($c === 1) {
				$commit_id = $line;
				break;
			}
			$c++;
		}

		return $commit_id;
	}


	public function mergeRemoteToLocalBySsh(&$_stream, $_remote_branch, $_local_branch, $_remote_repo = "origin", $_confirm = true)
	{
		# Confirm
		if ($_confirm) {
			$params = array();
			$params = array(
				"question" => "Do you merge branch from remote:" . $_remote_branch . " to local: " . $_local_branch . " ? [Y/n] : ",
				"stops" => array("n"),
				"starts" => array("Y"),
				"action" => "merge branch remote to local",
				"codes" => array("stop" => 0, "abort" => 1),
			);
			$this->confirm($params);
		}

		echo "merge branch remote to local, wait 10 seconds";

		# Fetch Prune
		$cmd = "git fetch\n";
		fwrite($_stream, $cmd);
		$this->wait(5);

		# Merge
		$c = 0;
		Remerge:
		$cmd = "git merge " . $_remote_repo . "/" . $_remote_branch . "\n";
		fwrite($_stream, $cmd);
		$this->wait(5);
		stream_set_blocking($_stream, false);
		$reset = false;
		$lines = array();
		while ($line = fgets($_stream)) {
			if (false !== strpos($line, "CONFLICT")) {
				$this->color(" error", 31);
				throw new Exception("Could not merge by conflict!", 3);
			}
			if (false !== strpos($line, "error: ")) {
				$reset = true;
				break;
			}
		}

		# Merge Broken
		if ($reset) {
			## Reset
			$cmd = "git reset --hard\n";
			fwrite($_stream, $cmd);
			echo " git reset --hard, wait 5 seconds";
			$this->wait(5);
			stream_set_blocking($_stream, false);

			$c++;
			if ($c === 3) {
				$this->color(" error", 31);
				throw new Exception("Internal: Could not reset hard.", 3);
			}

			goto Remerge;
		}

		$this->color(" done", 32);
	}

	public function clearSymfonyCacheBySsh(&$_stream)
	{
		echo "prod cache clear, wait 5 seconds";

		$cmd = "php mc/app/console cache:clear -env=prod --no-warmup";
		fwrite($_stream, $cmd);
		$this->wait(5);
		stream_set_blocking($_stream, false);
		$this->color(" done", 32);
	}


	public function chownBySsh(&$_stream, $_path, $_own_group)
	{
		echo "change owner to " . $_own_group . "... ";

		$cmd = "chown -Rc " . $_own_group . " " . $_path . "\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
	}


	public function findDirChmodBySsh(&$_stream, $_path, $_mode, $_not_paths = array())
	{
		$not_path = "";
		if (! empty($_not_paths)) {
			foreach ($_not_paths as $_not_path) {
				$not_path .= " -not -path '" . $_not_path . "*'";
			}
		}

		echo "change directory mode " . $_mode . "... ";
		$cmd = "find " . $_path . $not_path . " -type d -print0 | xargs -0 chmod " . $_mode . "\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
	}


	public function findFileChmodBySsh(&$_stream, $_path, $_mode, $_not_paths = array(), $_not_names = array())
	{
		$not_path = "";
		if (! empty($_not_paths)) {
			foreach ($_not_paths as $_not_path) {
				$not_path .= " -not -path '" . $_not_path . "*'";
			}
		}

		$not_name = "";
		if (! empty($_not_names)) {
			foreach ($_not_names as $_not_name) {
				$not_name .= " -not -name '" . $_not_name . "'";
			}
		}

		echo "change file mode " . $_mode . "... ";
		$cmd = "find " . $_path . "/" . $not_path . $not_name . " -type f -print0 | xargs -0 chmod " . $_mode . "\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
	}


	public function chmodBySsh(&$_stream, $_paths, $_mode)
	{
		if (is_array($_paths)) {
			$path = "";
			foreach ($_paths as $_path) {
				$path .= " " . $_path;
			}
		} else {
			$path = " " . $_paths;
		}

		echo "change mode... ";
		$cmd = "chmod " . $_mode . $path . "\n";
		fwrite($_stream, $cmd);
		sleep(1);
		stream_set_blocking($_stream, false);
		$this->color("done", 32);
	}

}
