<?php
class ConnectionManager {
    private $connections;
    private $connections_data;
    private $driver;
    private $charset;
    public function __construct($connections_data, $driver = 'mysql', $charset = 'utf8') {
        $this->connections = array();
        $this->connections_data = $connections_data;
        $this->driver = $driver;
        $this->charset = $charset;
    }
    private function connect($connection_name) {
        //Check if the connection data exist
        if (!array_key_exists($connection_name, $this->connections_data)) {
            throw new Exception("There si no connection data defined for the connection '$connection_name'");
        }

        //Check if already connected
        if (array_key_exists($connection_name, $this->connections)) {
            return true;
        }

        $connection_data = $this->connections_data[$connection_name];

        $host = $connection_data['host'];
        $user = $connection_data['user'];
        $pass = $connection_data['pass'];
        $db = $connection_data['db'];

        $dsn = "{$this->driver}:host=$host;dbname=$db;charset={$this->charset}";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $opt);
            $this->connections[$connection_name] = $pdo;
            return true;
        } catch (PDOException  $e) {
            throw new Exception('Connection failed: '.$e->getMessage());
        }
    }

    public function query($connection_name, $query, $data = array()) {
        //Check if the connection data exist
        if (!array_key_exists($connection_name, $this->connections_data)) {
            throw new Exception("There si no connection data defined for the connection '$connection_name'");
        }
        
        //Check if already connected, connect if not connected
        if (!array_key_exists($connection_name, $this->connections)) {
            $this->connect($connection_name);
        }
        
        $pdo = $this->connections[$connection_name];
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($data);
        return $stmt;    
    }
}