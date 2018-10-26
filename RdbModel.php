<?php

class RdbModel
{
	# Init
	public $confs = array();
	public $Pdo = null;
	public $Smtp = null;
	public $locked = null;
	public $locked_tables = array();
	public $locked_type = null;
	public $auto_commit = null;


	public function __construct($_confs)
	{
		$this->confs = $_confs;

		if ($this->confs['source'] === 'sqlite') {
			$this->confs['dsn'] = $this->confs['source'] . ':host=' . $this->confs['host'] . ';port=' . $this->confs['port'] . ';dbname=' . $this->confs['schema'] . ';charset=' . $this->confs['charset'];
		} else {
			$this->confs['dsn'] = $this->confs['source'] . ':host=' . $this->confs['host'] . ';dbname=' . $this->confs['schema'];
		}

		$this->Pdo = new PDO($this->confs['dsn'], $this->confs['user'], $this->confs['password']);
		$this->Pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}


	public function describe()
	{
		# Column Get
		$sql =<<<DOC
			DESCRIBE
				{$this->useTable}
DOC;

		$this->Stmt = $this->Pdo->prepare($sql);
		$this->Stmt->execute();
		$columns = array();
		$columns = $this->Stmt->fetchAll();
		$this->Stmt->closeCursor();

		$this->Describe = $columns;
		foreach ($columns as $i => $column) {
			# Primary Key Get
			if ($column['Key'] === 'PRI') {
				$this->PrimaryKey = $column['Field'];
			}
			# Column Name Paremeter
			$this->Column[$i] = $column['Field'];
		}
	}
	

    public function select($_sql, $_binds = array())
	{
		# Prepared Statement
		$this->Stmt = $this->Pdo->prepare($_sql);
		$this->Stmt->execute($_binds);

		# Fetch
		$selects = $this->Stmt->fetchAll();

		# Return
		return $selects;
	}


	public function insert($_sql, $_binds = array(), $_primary_key = 'id')
	{
		# Prepared Statement
		$this->Stmt = $this->Pdo->prepare($_sql);
		$flag = $this->Stmt->execute($_binds);

		# Last Insert ID
		$result = $flag ? $this->Pdo->lastInsertId($_primary_key) : $flag;

		# Return
		return $result;
	}


	public function update($_sql, $_binds = array())
	{
		# Prepared Statement
		$this->Stmt = $this->Pdo->prepare($_sql);
		$result = $this->Stmt->execute($_binds);

		# Return
		return $result;
	}


	public function closeCursor()
	{
		if (is_resource($this->Stmt)) {
			$this->Stmt->closeCursor();
		}
	}


	public function disconnectDatabase()
	{
		$this->Stmt = null;
		$this->Pdo = null;
	}


    public function lockTableForWriter($_tables)
    {
        $tables = ! is_array($_tables) ? array($_tables) : $_tables;
        sort($tables);
        $this->locked_tables = $tables;
        $table_serial = implode(' WRITE, ', $tables);

        # SQL
        $sql = 'LOCK TABLES ' . $table_serial . ' WRITE';
        if (! $this->Pdo->query($sql)) {
            throw new PdoException('Database Failed: Could not write lock table', 500);
        }
        $this->locked = true;
        $this->locked_type = 'write';
    }


    public function lockTableForReader($_tables)
    {
        $tables = ! is_array($_tables) ? array($_tables) : $_tables;
        sort($tables);
        $this->locked_tables = $tables;
        $table_serial = implode(' READ, ', $tables);

        # SQL
        $sql = 'LOCK TABLES ' . $table_serial . ' READ';
        if (! $this->Pdo->query($sql)) {
            throw new PdoException('Database Failed: Could not read lock table', 500);
        }
        $this->locked = true;
        $this->locked_type = 'read';
    }


    public function unlockTable()
    {
        if (! $this->Pdo->query('UNLOCK TABLES')) {
            throw new PdoException('Database Failed: Could not unlock table', 500);
        }

        $this->locked = false;
        $this->locked_type = null;
        $this->locked_tables = null;
    }


    public function isAutoCommit()
    {
        foreach ($this->Pdo->query('SELECT @@autocommit') as $row) {
            if (isset($row[0]['@@autocommit'])) {
                return true;
            }
            return false;
        }
    }


    public function onAutoCommit()
    {
        if (! $this->Pdo->query('SET AUTOCOMMIT = 1')) {
            throw new PdoException('Database Failed: Could not set autocommit', 500);
        }
        $this->auto_commit = 1;
    }


    public function unAutoCommit()
    {
        if (! $this->Pdo->query('SET AUTOCOMMIT = 0')) {
            throw new PdoException('Database Failed: Could not set unautocommit', 500);
        }
        $this->auto_commit = 0;
    }


    public function begin($_tables = [], $_lock_type = null)
    {
        if (empty($_tables)) {
            throw new PdoException('The passed 1st augument is empty.', 500);
        }

        $tables = ! is_array($_tables) ? array($_tables) : $_tables;

        # Auto Commit Unavairable
        $this->isAutoCommit() ? $this->unAutoCommit() : null;

        # Write Lock
        if ($_lock_type === 'write') {
            $this->writeLock($tables);
        # Read Lock
        } else if ($_lock_type === 'read') {
            $this->readLock($tables);
        }

        # Transaction
        $this->Pdo->beginTransaction();
    }


    public function commit()
    {
        # Unlock
        $this->locked ? $this->unlock() : null;

        # Auto Commit Avairable
        ! $this->auto_commit ? $this->onAutoCommit() : null;

        # Commit
        $this->Pdo->inTransaction() ? $this->Pdo->commit() : null;
    }


    public function rollback()
    {
        # Unlock
        $this->locked ? $this->unlock() : null;

        # Auto Commit Avairable
        ! $this->auto_commit ? $this->onAutoCommit() : null;

        # Rollback
        $this->Pdo->inTransaction() ? $this->Pdo->rollBack() : null;
    }

}
