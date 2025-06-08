<!DOCTYPE HTML>
<?php

session_start();

class Reservas {
    private $server;
    private $user;
    private $pass;
    private $dbname;
    private $conn;

    public function __construct() {
        $this->server = "localhost";
        $this->user = "DBUSER2025";
        $this->pass = "DBPSWD2025";
        $this->dbname = "reservas";
    }

    private function conectar() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->conn = new mysqli($this->server, $this->user, $this->pass, $this->dbname);
        } catch (mysqli_sql_exception $e) {
            if (str_contains($e->getMessage(), "Unknown database")) {
                echo "<h2>Error: La base de datos '{$this->dbname}' no existe. Por favor, créala antes de continuar.</h2>";
            } else {
                echo "<h2'>Error de conexión: " . htmlspecialchars($e->getMessage()) . "</h2>";
            }
            exit;
        }
    }

    public function registrarUsuario($dni, $nombre, $apellidos, $correo, $contra) {
        if($dni === '' || $nombre === '' || $apellidos === '' || $correo === '' || $contra === '') {
            echo "<p>Por favor, rellena todos los campos de registro.</p>";
        } else {

            $this->conectar();
    
            if ($this->conn->connect_error) {
                die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
            }
    
            $stmt = $this->conn->prepare("INSERT INTO usuario (dni, nombre, apellidos, correo, contra) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $dni, $nombre, $apellidos, $correo, $contra);
    
            if ($stmt->execute()) {
                $_SESSION['dnilogin'] = $dni;
                echo "<p>Cuenta creada correctamente. Redirigiendo...</p>";
                $stmt->close();
                $this->conn->close();
                header("Location: reservas.php");
                exit();
            } else {
                echo "<p>Error al insertar el registro: " . $stmt->error . "</p>";
            }
    
            $stmt->close();
            $this->conn->close();
        }
    }

    public function inicioSesion($dni, $contra) {
        if($dni === '' || $contra === '') {
            echo "<p>Por favor, rellena todos los campos de inicio de sesión.</p>";
        } else {
            $this->conectar();
    
            if ($this->conn->connect_error) {
                die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
            }
    
            $sql = "SELECT dni, contra FROM usuario WHERE dni='$dni'";
            $resultado = $this->conn->query($sql);
    
            if($resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                $contraresult = $row['contra'];
                if ($contraresult === $contra) {
                    $_SESSION['dnilogin'] = $dni;
                    echo "<p>Inicio de sesión correcto. Redirigiendo...</p>";
                    $this->conn->close();
                    header("Location: reservas.php");
                    exit();
                } else {
                    echo "<p>Credenciales de inicio de sesión inválidas</p>";
                }
            } else {
                echo "<p>Credenciales de inicio de sesión inválidas</p>";
            }
    
            $this->conn->close();
        }
    }

    public function cerrarSesion() {
        session_unset();
        session_destroy();
        echo "<p>Sesión cerrada correctamente. Redirigiendo...</p>"; 
        header("Location: reservas.php");
        exit();
    }

    public function mostrarImagenes($idrec) {
        $sql = "SELECT img, descripcion FROM imagenes WHERE id_recurso = " . $idrec;
        $result = $this->conn->query($sql);

        $imagenes = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $imagenes[] = $row;
            }
        }

        foreach ($imagenes as $imagen) {
            echo "<img src='/multimedia/imagenes/" . $imagen['img'] . "' alt='" . $imagen['descripcion'] . "'>";
        }
    }

    public function obtenerRecursosTuristicos() {
        $this->conectar();

        if ($this->conn->connect_error) {
            die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
        }

        $sql = "SELECT id, nombre, tipo, descripcion, precio, capacidad FROM recursoturistico";
        $result = $this->conn->query($sql);

        $recursos = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $recursos[] = $row;
            }
        } else {
            echo "<p>No hay recursos turísticos disponibles. Importa los datos para continuar.</p>";
            $this->conn->close();
            return;
        }

        $selected_resource_id = null;
        if (isset($_POST['recursos_seleccionados']) && is_array($_POST['recursos_seleccionados']) && count($_POST['recursos_seleccionados']) > 0) {
            $selected_resource_id = $_POST['recursos_seleccionados'][0];
        }

        foreach ($recursos as $recurso) {
            $checked_attr = ($selected_resource_id == $recurso['id']) ? 'checked' : '';
            
            echo "<p><input type='radio' name='recursos_seleccionados[]' value='" . htmlspecialchars($recurso['id']) . "' " . $checked_attr . ">" . htmlspecialchars($recurso['id']) . ". " . htmlspecialchars($recurso['nombre']) . " - Tipo: " . htmlspecialchars($recurso['tipo']) . " - Descripción: " . htmlspecialchars($recurso['descripcion']) . " - Precio: " . htmlspecialchars($recurso['precio']) . "€ - Capacidad máxima: " . htmlspecialchars($recurso['capacidad']) . " personas.</p>";
            $this->mostrarImagenes($recurso['id']);
        }

        $this->conn->close();
    }

    public function fechaDisponible($idrec, $fecha_ini, $fecha_fin, $personas) {
        $this->conectar();

        if ($this->conn->connect_error) {
            die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
        }

        // Obtener capacidad máxima del recurso
        $sqlCap = "SELECT capacidad FROM recursoturistico WHERE id = ?";
        $stmtCap = $this->conn->prepare($sqlCap);
        $stmtCap->bind_param("i", $idrec);
        $stmtCap->execute();
        $resultCap = $stmtCap->get_result();

        if ($resultCap->num_rows == 0) {
            $stmtCap->close();
            $this->conn->close();
            return false; // recurso no existe
        }

        $rowCap = $resultCap->fetch_assoc();
        $capacidadmax = $rowCap['capacidad'];
        $stmtCap->close();

        // Preparar consulta para sumar reservas ocupadas en un día
        $sqlReserva = "SELECT SUM(cantidad_personas) AS sumcant FROM reservas WHERE id_recurso = ? AND NOT (fecha_fin < ? OR fecha_ini > ?)";
        $stmtReserva = $this->conn->prepare($sqlReserva);

        $period = new DatePeriod(
            new DateTime($fecha_ini),
            new DateInterval('P1D'),
            (new DateTime($fecha_fin))->modify('+1 day') // para incluir fecha_fin
        );

        foreach ($period as $date) {
            $dia = $date->format('Y-m-d');
            $stmtReserva->bind_param("iss", $idrec, $dia, $dia);
            $stmtReserva->execute();
            $resultReserva = $stmtReserva->get_result();
            $ocupado = 0;
            if ($resultReserva->num_rows > 0) {
                $rowReserva = $resultReserva->fetch_assoc();
                $ocupado = $rowReserva['sumcant'] ?? 0;
            }

            // Comprobar si queda espacio para el día
            if (($ocupado + $personas) > $capacidadmax) {
                $stmtReserva->close();
                $this->conn->close();
                return false; // No hay capacidad suficiente en al menos un día
            }
        }

        $stmtReserva->close();
        $this->conn->close();
        return true; // Hay disponibilidad para todo el rango
    }

    public function calcularPrecio($idrec, $fecha_ini, $fecha_fin, $personas) {
        if($idrec === '' || $fecha_ini === '' || $fecha_fin === '' || $personas === '' || $personas <= 0) {
            echo "<p>Por favor, seleccione el recurso, fechas y cantidad de personas</p>";
            return -1;
        } else {
            if ($this->fechaDisponible($idrec, $fecha_ini, $fecha_fin, $personas)) {
                $this->conectar();
        
                if ($this->conn->connect_error) {
                    die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
                }
        
                $sql = "SELECT precio FROM recursoturistico WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $idrec);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $precio_unitario = $row['precio'];

                    $fecha_inicio = new DateTime($fecha_ini);
                    $fecha_fin_dt = new DateTime($fecha_fin);
                    $interval = $fecha_inicio->diff($fecha_fin_dt);
                    $dias = $interval->days + 1; // incluir ambos días

                    $preciototal = $precio_unitario * $personas * $dias;
                    echo "<p>La cantidad total a pagar es de " . $preciototal . "€</p>";

                    $stmt->close();
                    $this->conn->close();
                    return $preciototal;
                }
        
                $stmt->close();
                $this->conn->close();
                return -1;
            } else {
                echo "<p>Lo sentimos, no hay plazas suficientes para esas fechas. Prueba a bajar la cantidad de personas o cambia las fechas</p>";
                return -1;
            }
        }
    }

    public function realizarReserva($idrec, $fecha_ini, $fecha_fin, $personas) {
        $precio = $this->calcularPrecio($idrec, $fecha_ini, $fecha_fin, $personas);
        if ($precio >= 0) {
            if ( isset( $_SESSION['dnilogin']) ) {
                $this->conectar();
        
                if ($this->conn->connect_error) {
                    die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
                }
                $id = rand(1, 999999);
                $idusuario = $_SESSION['dnilogin'];
                $stmt = $this->conn->prepare("INSERT INTO reservas (id, id_usuario, id_recurso, fecha_ini, fecha_fin, cantidad_personas, precio) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $id, $idusuario, $idrec, $fecha_ini, $fecha_fin, $personas, $precio);

                if ($stmt->execute()) {
                    echo "<p>Reserva realizada correctamente por el usuario con dni " . $idusuario . ". Número de reserva: " . $id . "</p>";
                    echo "<p>ID recurso turístico: " . $idrec . ". Fecha inicio: " . $fecha_ini . ". Fecha fin: " . $fecha_fin . ". Cantidad de personas: " . $personas . ". PRECIO FINAL: " . $precio . "€.</p>";
                    $this->mostrarContactos($idrec);
                } else {
                    echo "<p>Error al insertar el registro: " . $stmt->error . "</p>";
                }
        
                $stmt->close();
                $this->conn->close();
            } else {
                echo "<p>Debe iniciar sesión para hacer una reserva</p>";
            }
        }
    }

    public function mostrarContactos($idrec) {
        $sql = "SELECT nombre, tipo, valor FROM contactos WHERE id_recurso = " . $idrec;
        $result = $this->conn->query($sql);

        $contactos = array();
        if ($result->num_rows > 0) {
            echo "<h3>Contáctos útiles</h3>";
            while ($row = $result->fetch_assoc()) {
                $contactos[] = $row;
            }

            foreach ($contactos as $contacto) {
                echo "<h4>" . $contacto['nombre'] . "</h4>
                <ul>
                    <li>Tipo: " . $contacto['tipo'] . "</li>
                    <li>Contacto: " . $contacto['valor'] . "</li>
                </ul>";
            }
        }
    }

    public function mostrarReservasUsuario($dniusuario) {
        $this->conectar();

        if ($this->conn->connect_error) {
            die("<p>Conexión fallida: " . $this->conn->connect_error . "</p>");
        }

        $stmt = $this->conn->prepare("SELECT r.id, r.fecha_ini, r.fecha_fin, r.cantidad_personas, r.precio, rt.nombre 
                                    FROM reservas r 
                                    JOIN recursoturistico rt ON r.id_recurso = rt.id 
                                    WHERE r.id_usuario = ?");
        $stmt->bind_param("s", $dniusuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<p>No tienes reservas realizadas.</p>";
        } else {
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>
                        Reserva #{$row['id']}: {$row['nombre']} del {$row['fecha_ini']} al {$row['fecha_fin']} 
                        - {$row['cantidad_personas']} personas - {$row['precio']}€

                        <form method='post' style='display:inline'>
                            <input type='hidden' name='id_reserva_anular' value='{$row['id']}'>
                            <button type='submit' onclick=\"return confirm('¿Estás seguro de que deseas anular esta reserva?');\">
                                Anular
                            </button>
                        </form>
                    </li>";
            }
            echo "</ul>";
        }

        $stmt->close();
        $this->conn->close();
    }

    public function anularReserva($idReserva, $dniusuario) {
        $this->conectar();

        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        $stmt = $this->conn->prepare("DELETE FROM reservas WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("is", $idReserva, $dniusuario);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<p>Reserva anulada correctamente.</p>";
        } else {
            echo "<p>No se pudo anular la reserva. Puede que no exista o no te pertenezca.</p>";
        }

        $stmt->close();
        $this->conn->close();
    }

    public function crearBaseDatos() {
        $this->conn = new mysqli($this->server, $this->user, $this->pass);
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        $sql_file = __DIR__ . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'reservas.sql';
        if (!file_exists($sql_file)) {
            die("Error: El archivo SQL no se encuentra en la ruta $sql_file");
        }
        $queries = file_get_contents($sql_file);

        $this->conn->query("DROP DATABASE IF EXISTS $this->dbname");
        $this->conn->query("CREATE DATABASE $this->dbname");
        $this->conn->select_db($this->dbname);

        if ($this->conn->multi_query($queries)) {
            do {
                if ($result = $this->conn->store_result()) {
                    $result->free();
                }
            } while ($this->conn->more_results() && $this->conn->next_result());

            echo "Base de datos y tablas creadas correctamente.";
        } else {
            echo "Error al crear la base de datos: " . $this->conn->error;
        }

        $this->conn->close();
    }

    public function importarCSV() {
        $this->conectar();
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        $archivo = __DIR__ . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'reservas.csv';
        $file = fopen($archivo, 'r');

        $estructura_tablas = [
            'id_recurso,nombre,tipo,valor' => 'contactos',
            'img,id_recurso,descripcion' => 'imagenes',
            'id,nombre,tipo,descripcion,precio,capacidad' => 'recursoturistico',
            'id,id_usuario,id_recurso,fecha_ini,fecha_fin,cantidad_personas,precio' => 'reservas',
            'dni,nombre,apellidos,correo,contra' => 'usuario',
        ];

        // Guardar filas temporalmente por tabla
        $datos_tablas = [];
        $current_table = null;
        $columns = [];

        while (($line = fgetcsv($file, 1000, ",")) !== false) {
            if (in_array(strtolower($line[0]), ['id', 'id_recurso', 'img', 'dni'])) {
                $columns = $line;
                $key = implode(',', $columns);
                $current_table = $estructura_tablas[$key] ?? null;
                continue;
            }

            if (!$current_table || empty($line[0])) continue;

            // Guardar fila para esa tabla
            $datos_tablas[$current_table][] = $line;
        }

        fclose($file);

        // Orden correcto de inserción para respetar FK
        $orden_tablas = ['usuario', 'recursoturistico', 'contactos', 'imagenes', 'reservas'];

        foreach ($orden_tablas as $tabla) {
            if (empty($datos_tablas[$tabla])) continue;

            // Obtener columnas para esa tabla
            $cols = array_keys($estructura_tablas);
            $cols_str = '';
            foreach ($estructura_tablas as $key => $value) {
                if ($value === $tabla) {
                    $cols_str = $key;
                    break;
                }
            }
            $cols_array = explode(',', $cols_str);
            $cols_sql = implode(',', $cols_array);

            foreach ($datos_tablas[$tabla] as $fila) {
                $escaped_values = array_map(function($value) {
                    return "'" . $this->conn->real_escape_string($value) . "'";
                }, $fila);

                $values_sql = implode(',', $escaped_values);

                $sql = "INSERT INTO $tabla ($cols_sql) VALUES ($values_sql)";
                if (!$this->conn->query($sql)) {
                    echo "Error en inserción en tabla $tabla: " . $this->conn->error . "<br>";
                }
            }
        }

        echo "Datos importados correctamente.";
        $this->conn->close();
    }

    public function exportarCSV() {
        $this->conectar();
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        $archivo = __DIR__ . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'reservas_export.csv';
        $file = fopen($archivo, 'w');

        $tablas = ['contactos', 'imagenes', 'recursoturistico', 'reservas', 'usuario'];

        foreach ($tablas as $tabla) {
            $result = $this->conn->query("SELECT * FROM $tabla");
            if ($result) {
                
                $columnas = array_keys($result->fetch_assoc());
                
                $result->data_seek(0);

                fputcsv($file, $columnas);

                while ($fila = $result->fetch_assoc()) {
                    fputcsv($file, $fila);
                }
            }
        }

        fclose($file);
        echo "Datos exportados correctamente a reservas_export.csv.";
        $this->conn->close();
    }

}
?>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>San Tirso de Abres - Reservas</title>
    <meta name ="author" content ="Álvaro Arias" />
    <meta name ="description" content ="Servicio de reservas de las rutas" />
    <meta name ="keywords" content ="reservas, formulario, recursos" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="estilo/layout.css" />
    <link rel=icon href="multimedia/imagenes/favicon.ico" type="image/vnd.microsoft.icon" />
</head>
<body>
    <header>
        <h1><a title="Enlace al índice" href="index.html">San Tirso de Abres</a></h1>
        <nav>
            <a title="Enlace al índice" href="index.html">Página principal</a>
            <a title="Enlace a la página de gastronomía" href="gastronomia.html">Gastronomía</a>
            <a title="Enlace a la página de rutas" href="rutas.html">Rutas</a>
            <a title="Enlace a la página de meteorología" href="meteorologia.html">Meteorología</a>
            <a title="Enlace a la página del juego" href="juego.html">Juego</a>
            <a title="Enlace a la página de reservas" href="reservas.php" class="active">Reservas</a>
            <a title="Enlace a la página de ayuda" href="ayuda.html">Ayuda</a>
        </nav>
    </header>
    <h2>Reservas</h2>
    <!-- Formulario para la administración de la base de datos
    <form action="reservas.php" method="post">
        <h3>Administración de Base de Datos</h3>
        <button type="submit" name="opcion" value="crear">Crear Base de Datos</button>
        <button type="submit" name="opcion" value="importar">Importar desde CSV</button>
        <button type="submit" name="opcion" value="exportar">Exportar a CSV</button>
    </form> -->
    <?php
    $reservas = new Reservas();

    /* Administración de la base de datos
    if (isset($_POST["opcion"]) && $_POST["opcion"] == "crear") {
        $reservas->crearBaseDatos();
    }

    if (isset($_POST["opcion"]) && $_POST["opcion"] == "importar") {
        $reservas->importarCSV();
    }

    if (isset($_POST["opcion"]) && $_POST["opcion"] == "exportar") {
        $reservas->exportarCSV();
    }*/

    $dni;
    $nombre;
    $apellidos;
    $correo;
    $contra;

    if (isset($_GET['logout'])) {
        $reservas->cerrarSesion();
    }

    if(isset($_POST["registro_submit"])) {
        $dni = $_POST["dnireg"];
        $nombre = $_POST["nombrereg"];
        $apellidos = $_POST["apellidosreg"];
        $correo = $_POST["correoreg"];
        $contra = $_POST["contraseñareg"];

        $reservas->registrarUsuario($dni, $nombre, $apellidos, $correo, $contra);
    }

    if(isset($_POST["login_submit"])) {
        $dni = $_POST["dnilog"];
        $contra = $_POST["contraseñalog"];

        $reservas->inicioSesion($dni, $contra);
    }
    if( isset( $_SESSION['dnilogin'] ) ) {
        echo "<p>Inicio de sesión correcto. Usuario con dni: " . $_SESSION['dnilogin'] . "</p>";
    } else {
        echo "<p>Inicie sesión para poder reservar los recursos turísticos</p>";
    }
    ?>
    <?php if (isset($_SESSION['dnilogin'])): ?>
        <form method="get" name="logoutForm">
            <button type="submit" name="logout">Cerrar sesión</button>
        </form>
    <?php else: ?>
        <?php
        $show_form = isset($_GET['show']) ? $_GET['show'] : 'login';
        ?>

        <?php if ($show_form === 'register'): ?>
            <section>
                <h3>Registro de usuario</h3>
                <p>Si ya tienes una cuenta, <a href="reservas.php?show=login">inicia sesión aquí</a>.</p>
                <form action="reservas.php?show=register" method="post" name="registroForm">
                    <p>DNI: <input type="text" name="dnireg"></p>
                    <p>Nombre: <input type="text" name="nombrereg"></p>
                    <p>Apellidos: <input type="text" name="apellidosreg"></p>
                    <p>Correo: <input type="text" name="correoreg"></p>
                    <p>Contraseña: <input type="text" name="contraseñareg"></p>
                    <p><button type="submit" name="registro_submit">Registrarse</button></p>
                </form>
            </section>
        <?php else: ?>
            <section>
                <h3>Inicio de sesión</h3>
                <form action="reservas.php?show=login" method="post" name="loginForm">
                    <p>DNI: <input type="text" name="dnilog"></p>
                    <p>Contraseña: <input type="text" name="contraseñalog"></p>
                    <p><button type="submit" name="login_submit">Iniciar sesión</button></p>
                </form>
                <p>¿No tienes cuenta? <a href="reservas.php?show=register">Regístrate aquí</a>.</p>
            </section>
        <?php endif; ?>
    <?php endif; ?>
    <section>
        <h3>Realizar reserva</h3>
        <form action="reservas.php" method="post" name="reservaForm">
            <?php
            $reservas->obtenerRecursosTuristicos();
            ?>
            <p>Fecha inicio: <input type="date" name="fecha_ini" value="<?php echo isset($_POST['fecha_ini']) ? htmlspecialchars($_POST['fecha_ini']) : ''; ?>" required></p>
            <p>Fecha fin: <input type="date" name="fecha_fin" value="<?php echo isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : ''; ?>" required></p>
            <p>Cantidad de personas: <input type="number" name="personas" min="1" value="<?php echo isset($_POST['personas']) ? htmlspecialchars($_POST['personas']) : ''; ?>" required></p>
            <p><button type="submit" name="precio_submit">Calcular precio</button></p>
            <p><button type="submit" name="reserva_submit" onclick="return confirm('¿Estás seguro de que deseas realizar esta reserva?');">Realizar reserva</button></p>
            <?php
            $idrec = '';
            $fecha_ini = '';
            $fecha_fin = '';
            $personas = '';
            
            if(isset($_POST["precio_submit"])) {
                if(isset($_POST["recursos_seleccionados"]) && is_array($_POST["recursos_seleccionados"])) {
                    $idrec = $_POST["recursos_seleccionados"][0];
                } else {
                    $idrec = '';
                }
                $fecha_ini = $_POST["fecha_ini"];
                $fecha_fin = $_POST["fecha_fin"];
                $personas = $_POST["personas"];

                $reservas->calcularPrecio($idrec, $fecha_ini, $fecha_fin, $personas);
            }
            if(isset($_POST["reserva_submit"])) {
                if(isset($_POST["recursos_seleccionados"]) && is_array($_POST["recursos_seleccionados"])) {
                    $idrec = $_POST["recursos_seleccionados"][0];
                } else {
                    echo "<p>Por favor, selecciona un recurso.</p>";
                    exit;
                }
                $fecha_ini = $_POST["fecha_ini"];
                $fecha_fin = $_POST["fecha_fin"];
                $personas = $_POST["personas"];

                $reservas->realizarReserva($idrec, $fecha_ini, $fecha_fin, $personas);
            }
            ?>
        </form>
    </section>
    <section>
        <h3>Mis reservas</h3>
        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_reserva_anular']) && isset($_SESSION['dnilogin'])) {
            $idReserva = intval($_POST['id_reserva_anular']);
            $reservas->anularReserva($idReserva, $_SESSION['dnilogin']);
        }
        if (isset($_SESSION['dnilogin'])) {
            $reservas->mostrarReservasUsuario($_SESSION['dnilogin']);
        } else {
            echo "<p>Debes iniciar sesión para ver tus reservas.</p>";
        }
        ?>
    </section>
</body>
</html>