<?php
require '../../vendor/autoload.php'; // Autoload de Composer para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Clase para la conexión a la base de datos
class dbConexion {
    var $dbhost = "localhost";
    var $username = "root";
    var $password = "";
    var $dbname = "proyecto_final";
    var $conn;

    function getConexion() {
        $con = mysqli_connect($this->dbhost, $this->username, $this->password, $this->dbname) or die("Connection failed: " . mysqli_connect_error());

        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        } else {
            $this->conn = $con;
        }
        return $this->conn;
    }
}

// Verifica si se ha enviado la solicitud para exportar
if ((isset($_POST['export']) && $_SERVER['REQUEST_METHOD'] === 'POST') || isset($_GET['export'])) {
    $exportType = $_POST['export'] ?? $_GET['export'];
    $db = new dbConexion();
    $connString = $db->getConexion();

    if ($exportType === 'pdf') {
        // Exportar a PDF
        include_once('../../assets/fpdf/fpdf.php');

        class PDF extends FPDF {
            function Header() {
                $this->Image('../../assets/img/icon.png',10,-1,30);
                $this->SetFont('Arial','B',13);
                $this->Cell(80);
                $this->Cell(95,10,'Lista de los pacientes',1,0,'C');
                $this->Ln(20);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
            }
        }

        $result = mysqli_query($connString, "SELECT * FROM customers") or die("database error:". mysqli_error($connString));

        $pdf = new PDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial','B',12);
        $w = array(10, 25, 70, 70, 25, 35, 25, 25);

        $pdf->Cell(10,12,'#',1);
        $pdf->Cell(25,12,'DNI',1);
        $pdf->Cell(70,12,'NOMBRES',1);
        $pdf->Cell(70,12,'APELLIDOS',1);
        $pdf->Cell(25,12,'SEGURO',1);
        $pdf->Cell(35,12,'TELEFONO',1);
        $pdf->Cell(25,12,'SEXO',1);
        $pdf->Cell(25,12,'USUARIO',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);

        foreach($result as $row) {
            $pdf->Cell($w[0],6,$row['codpaci'],1);
            $pdf->Cell($w[1],6,utf8_decode($row['dnipa']),1);
            $pdf->Cell($w[2],6,utf8_decode($row['nombrep']),1);
            $pdf->Cell($w[3],6,utf8_decode($row['apellidop']),1);
            $pdf->Cell($w[4],6,utf8_decode($row['seguro']),1);
            $pdf->Cell($w[5],6,utf8_decode($row['tele']),1);
            $pdf->Cell($w[6],6,utf8_decode($row['sexo']),1);
            $pdf->Cell($w[7],6,utf8_decode($row['usuario']),1);
            $pdf->Ln();
        }

        $pdf->Output('pacientes.pdf', 'D');
        exit;
    } elseif ($exportType === 'excel') {
        // Exportar a Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'DNI');
        $sheet->setCellValue('C1', 'Nombres');
        $sheet->setCellValue('D1', 'Apellidos');
        $sheet->setCellValue('E1', 'Seguro');
        $sheet->setCellValue('F1', 'Telefono');
        $sheet->setCellValue('G1', 'Sexo');
        $sheet->setCellValue('H1', 'Usuario');

        $result = mysqli_query($connString, "SELECT * FROM customers") or die("database error:". mysqli_error($connString));

        $rowNumber = 2;
        while ($row = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A' . $rowNumber, $row['codpaci']);
            $sheet->setCellValue('B' . $rowNumber, $row['dnipa']);
            $sheet->setCellValue('C' . $rowNumber, utf8_encode($row['nombrep']));
            $sheet->setCellValue('D' . $rowNumber, utf8_encode($row['apellidop']));
            $sheet->setCellValue('E' . $rowNumber, $row['seguro']);
            $sheet->setCellValue('F' . $rowNumber, $row['tele']);
            $sheet->setCellValue('G' . $rowNumber, $row['sexo']);
            $sheet->setCellValue('H' . $rowNumber, $row['usuario']);
            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="customers.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte</title>
</head>
<body>
    <h1>Reporte de Clientes</h1>
    <form method="post">
        <button type="submit" name="export" value="pdf" class="btn btn-warning">Exportar a PDF</button>
        <button type="submit" name="export" value="excel" class="btn btn-success">Exportar a Excel</button>
    </form>
</body>
</html>
