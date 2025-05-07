<?php

// config/database.php

define('BASE_URL', '/local-farmer-connect');



class Database {

    private $host = "localhost";

    private $db_name = "kitk8331_local-farmer-connect";

    private $username = "kitk8331_kittod";

    private $password = "radenganda4900";

    public $conn;



    public function getConnection() {

        $this->conn = null;

        try {

            $this->conn = new PDO(

                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,

                $this->username,

                $this->password

            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $e) {

            echo "Connection Error: " . $e->getMessage();

        }

        return $this->conn;

    }

}



session_start();



function isLoggedIn() {

    return isset($_SESSION['user_id']);

}



function getUserRole() {

    return $_SESSION['user_role'] ?? null;

}



function isAdmin() {

    return getUserRole() === 'Admin';

}



function isFarmer() {

    return getUserRole() === 'Farmer';

}



function isCustomer() {

    return getUserRole() === 'Customer';

}



function setFlashMessage($type, $message) {

    $_SESSION['flash'] = [

        'type' => $type,

        'message' => $message

    ];

}



function getFlashMessage() {

    if (isset($_SESSION['flash'])) {

        $flash = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $flash;

    }

    return null;

}



// config/database.php



function uploadImage($file, $directory = 'uploads/') {

    // Pastikan directory diakhiri dengan slash

    $directory = rtrim($directory, '/') . '/';

    $targetDir = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/' . $directory;



    // Buat directory jika belum ada

    if (!file_exists($targetDir)) {

        mkdir($targetDir, 0777, true);

    }



    // Generate nama file unik

    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    $fileName = uniqid() . '_' . time() . '.' . $extension;

    $targetFile = $targetDir . $fileName;



    // Validasi file

    $allowedTypes = ['jpg', 'jpeg', 'png'];

    if (!in_array($extension, $allowedTypes)) {

        return false;

    }



    if ($file["size"] > 5000000) {

        return false;

    }



    if (move_uploaded_file($file["tmp_name"], $targetFile)) {

        return $fileName;

    }



    return false;

}



function initializeUploadDirectories() {

    $uploadDirs = [

        'uploads/products',

        'uploads/stores',

        'uploads/users'

    ];



    foreach ($uploadDirs as $dir) {

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/' . $dir;

        if (!file_exists($fullPath)) {

            mkdir($fullPath, 0777, true);

        }



        // Create .htaccess if doesn't exist

        $htaccess = $fullPath . '/.htaccess';

        if (!file_exists($htaccess)) {

            file_put_contents($htaccess, "Options -Indexes\n\n<FilesMatch \"\.(jpg|jpeg|png)$\">\n    Allow from all\n</FilesMatch>");

        }

    }

}



// Call this function when initializing the application

initializeUploadDirectories();