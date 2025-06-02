<?php

/**
 * Clase FtpClient para manejar conexiones y operaciones FTP.
 *
 * Esta clase proporciona métodos para conectar, iniciar sesión,
 * subir archivos, descargar archivos, listar directorios y cerrar
 * la conexión FTP.
 */
class FtpClient
{
    private $ftp_conn; // Recurso de conexión FTP
    private $host;     // Host del servidor FTP
    private $port;     // Puerto del servidor FTP (por defecto 21)
    private $username; // Nombre de usuario FTP
    private $password; // Contraseña FTP
    private $passive_mode; // Modo pasivo (true/false)
    private $timeout;  // Tiempo de espera para la conexión (en segundos)

    /**
     * Constructor de la clase FtpClient.
     *
     * @param string $host El host del servidor FTP.
     * @param string $username El nombre de usuario para la conexión FTP.
     * @param string $password La contraseña para la conexión FTP.
     * @param int $port El puerto del servidor FTP (por defecto 21).
     * @param bool $passive_mode Si se debe usar el modo pasivo (por defecto true).
     * @param int $timeout El tiempo de espera para la conexión (por defecto 90 segundos).
     */
    public function __construct($host, $username, $password, $port = 21, $passive_mode = true, $timeout = 90)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->passive_mode = $passive_mode;
        $this->timeout = $timeout;
        $this->ftp_conn = null; // Inicializar la conexión como nula
    }

    /**
     * Conecta y autentica con el servidor FTP.
     *
     * @return bool True si la conexión y el login son exitosos, false en caso contrario.
     */
    public function connect()
    {
        // Intentar establecer la conexión FTP
        $this->ftp_conn = ftp_connect($this->host, $this->port, $this->timeout);

        if (!$this->ftp_conn) {
            error_log("Error: No se pudo conectar al servidor FTP {$this->host}:{$this->port}");
            return false;
        }

        // Intentar iniciar sesión
        $login_result = ftp_login($this->ftp_conn, $this->username, $this->password);

        if (!$login_result) {
            error_log("Error: No se pudo iniciar sesión con el usuario {$this->username} en {$this->host}");
            $this->close(); // Cerrar la conexión si el login falla
            return false;
        }

        // Establecer el modo pasivo si está habilitado
        if ($this->passive_mode) {
            if (!ftp_pasv($this->ftp_conn, true)) {
                error_log("Advertencia: No se pudo establecer el modo pasivo en el servidor FTP.");
            }
        }

        return true;
    }

    /**
     * Sube un archivo al servidor FTP.
     *
     * @param string $local_file La ruta completa del archivo local a subir.
     * @param string $remote_file La ruta completa en el servidor FTP donde se guardará el archivo.
     * @param int $mode El modo de transferencia (FTP_ASCII o FTP_BINARY, por defecto FTP_BINARY).
     * @return bool True si el archivo se subió exitosamente, false en caso contrario.
     */
    public function uploadFile($local_file, $remote_file, $mode = FTP_BINARY)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        if (!file_exists($local_file)) {
            error_log("Error: El archivo local '{$local_file}' no existe.");
            return false;
        }

        if (ftp_put($this->ftp_conn, $remote_file, $local_file, $mode)) {
            return true;
        } else {
            error_log("Error: No se pudo subir el archivo '{$local_file}' a '{$remote_file}'.");
            return false;
        }
    }

    /**
     * Descarga un archivo del servidor FTP.
     *
     * @param string $remote_file La ruta completa del archivo en el servidor FTP a descargar.
     * @param string $local_file La ruta completa donde se guardará el archivo localmente.
     * @param int $mode El modo de transferencia (FTP_ASCII o FTP_BINARY, por defecto FTP_BINARY).
     * @return bool True si el archivo se descargó exitosamente, false en caso contrario.
     */
    public function downloadFile($remote_file, $local_file, $mode = FTP_BINARY)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        if (ftp_get($this->ftp_conn, $local_file, $remote_file, $mode)) {
            return true;
        } else {
            error_log("Error: No se pudo descargar el archivo '{$remote_file}' a '{$local_file}'.");
            return false;
        }
    }

    /**
     * Lista los contenidos de un directorio en el servidor FTP.
     *
     * @param string $directory El directorio a listar (por defecto el directorio actual).
     * @return array|false Un array con los nombres de los archivos y directorios, o false en caso de error.
     */
    public function listDirectory($directory = '.')
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        $contents = ftp_nlist($this->ftp_conn, $directory);

        if ($contents === false) {
            error_log("Error: No se pudo listar el directorio '{$directory}'.");
        }
        return $contents;
    }

    /**
     * Cambia el directorio de trabajo en el servidor FTP.
     *
     * @param string $directory El directorio al que se desea cambiar.
     * @return bool True si el cambio de directorio fue exitoso, false en caso contrario.
     */
    public function changeDirectory($directory)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        if (ftp_chdir($this->ftp_conn, $directory)) {
            return true;
        } else {
            error_log("Error: No se pudo cambiar al directorio '{$directory}'.");
            return false;
        }
    }

    /**
     * Crea un nuevo directorio en el servidor FTP.
     *
     * @param string $directory_name El nombre del directorio a crear.
     * @return string|false El nombre del directorio creado si es exitoso, o false en caso de error.
     */
    public function makeDirectory($directory_name)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        $result = ftp_mkdir($this->ftp_conn, $directory_name);
        if ($result === false) {
            error_log("Error: No se pudo crear el directorio '{$directory_name}'.");
        }
        return $result;
    }

    /**
     * Elimina un archivo del servidor FTP.
     *
     * @param string $file_name El nombre del archivo a eliminar.
     * @return bool True si el archivo se eliminó exitosamente, false en caso contrario.
     */
    public function deleteFile($file_name)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        if (ftp_delete($this->ftp_conn, $file_name)) {
            return true;
        } else {
            error_log("Error: No se pudo eliminar el archivo '{$file_name}'.");
            return false;
        }
    }

    /**
     * Elimina un directorio vacío del servidor FTP.
     *
     * @param string $directory_name El nombre del directorio a eliminar.
     * @return bool True si el directorio se eliminó exitosamente, false en caso contrario.
     */
    public function removeDirectory($directory_name)
    {
        if (!$this->ftp_conn) {
            error_log("Error: No hay conexión FTP activa. Por favor, conéctese primero.");
            return false;
        }

        if (ftp_rmdir($this->ftp_conn, $directory_name)) {
            return true;
        } else {
            error_log("Error: No se pudo eliminar el directorio '{$directory_name}'.");
            return false;
        }
    }


    /**
     * Cierra la conexión FTP.
     *
     * @return bool True si la conexión se cerró exitosamente, false en caso contrario.
     */
    public function close()
    {
        if ($this->ftp_conn) {
            return ftp_close($this->ftp_conn);
        }
        return false; // No hay conexión para cerrar
    }

    /**
     * Destructor de la clase para asegurar que la conexión FTP se cierre.
     */
    public function __destruct()
    {
        $this->close();
    }
}

// --- EJEMPLO DE USO ---
/*
// Configura tus credenciales FTP
$ftp_host = 'your_ftp_host.com'; // Por ejemplo: 'ftp.example.com'
$ftp_user = 'your_ftp_username';
$ftp_pass = 'your_ftp_password';
$ftp_port = 21; // Puerto FTP estándar

// Crea una instancia de la clase FtpClient
$ftp = new FtpClient($ftp_host, $ftp_user, $ftp_pass, $ftp_port);

// Intenta conectar y autenticar
if ($ftp->connect()) {
    echo "Conexión FTP exitosa.\n";

    // Ejemplo: Subir un archivo
    $local_file_to_upload = 'ruta/a/tu/archivo_local.txt'; // Asegúrate de que este archivo exista
    $remote_upload_path = 'public_html/nuevo_archivo.txt';
    if ($ftp->uploadFile($local_file_to_upload, $remote_upload_path)) {
        echo "Archivo subido exitosamente: {$remote_upload_path}\n";
    } else {
        echo "Fallo al subir el archivo.\n";
    }

    // Ejemplo: Listar el contenido del directorio actual
    echo "Contenido del directorio actual:\n";
    $contents = $ftp->listDirectory();
    if ($contents) {
        foreach ($contents as $item) {
            echo "- " . $item . "\n";
        }
    } else {
        echo "No se pudo listar el directorio.\n";
    }

    // Ejemplo: Descargar un archivo
    $remote_file_to_download = 'public_html/archivo_remoto.zip'; // Asegúrate de que este archivo exista en el FTP
    $local_download_path = 'ruta/para/guardar/archivo_descargado.zip';
    if ($ftp->downloadFile($remote_file_to_download, $local_download_path)) {
        echo "Archivo descargado exitosamente: {$local_download_path}\n";
    } else {
        echo "Fallo al descargar el archivo.\n";
    }

    // Ejemplo: Cambiar de directorio
    if ($ftp->changeDirectory('public_html')) {
        echo "Cambiado al directorio 'public_html'.\n";
        // Listar el contenido del nuevo directorio
        echo "Contenido de public_html:\n";
        $contents_public_html = $ftp->listDirectory();
        if ($contents_public_html) {
            foreach ($contents_public_html as $item) {
                echo "- " . $item . "\n";
            }
        }
    } else {
        echo "Fallo al cambiar de directorio.\n";
    }

    // Ejemplo: Crear un directorio (descomentar para usar)
    // $new_dir = 'mi_nuevo_directorio_test';
    // if ($ftp->makeDirectory($new_dir)) {
    //     echo "Directorio '{$new_dir}' creado exitosamente.\n";
    // } else {
    //     echo "Fallo al crear el directorio.\n";
    // }

    // Ejemplo: Eliminar un archivo (descomentar para usar, ¡cuidado!)
    // $file_to_delete = 'public_html/archivo_a_eliminar.txt';
    // if ($ftp->deleteFile($file_to_delete)) {
    //     echo "Archivo '{$file_to_delete}' eliminado exitosamente.\n";
    // } else {
    //     echo "Fallo al eliminar el archivo.\n";
    // }

    // Ejemplo: Eliminar un directorio vacío (descomentar para usar, ¡cuidado!)
    // $dir_to_remove = 'mi_nuevo_directorio_test';
    // if ($ftp->removeDirectory($dir_to_remove)) {
    //     echo "Directorio '{$dir_to_remove}' eliminado exitosamente.\n";
    // } else {
    //     echo "Fallo al eliminar el directorio.\n";
    // }


    // Cierra la conexión FTP al finalizar
    $ftp->close();
    echo "Conexión FTP cerrada.\n";
} else {
    echo "Fallo al conectar o iniciar sesión FTP.\n";
}
*/
?>