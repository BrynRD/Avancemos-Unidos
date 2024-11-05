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
                $this->Cell(95,10,'Lista de las especialidades',1,0,'C');
                $this->Ln(20);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
            }
        }

        $result = mysqli_query($connString, "SELECT * FROM specialty") or die("database error:". mysqli_error($connString));

        $pdf = new PDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial','B',10);
        $w = array(10, 200, 70);

        $pdf->Cell($w[0],12,'#',1);
        $pdf->Cell($w[1],12,'ESPECIALIDAD',1);
        $pdf->Cell($w[2],12,'FECHA',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);

        foreach($result as $row) {
            $pdf->Cell($w[0],6,$row['codespe'],1);
            $pdf->Cell($w[1],6,utf8_decode($row['nombrees']),1);
            $pdf->Cell($w[2],6,$row['fecha_create'],1);
            $pdf->Ln();
        }

        $pdf->Output('especialidades.pdf', 'D');
        exit;

    } elseif ($exportType === 'excel') {
        // Exportar a Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'Especialidad');
        $sheet->setCellValue('C1', 'Fecha');

        $result = mysqli_query($connString, "SELECT * FROM specialty") or die("database error:". mysqli_error($connString));

        $rowNumber = 2;
        while ($row = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A' . $rowNumber, $row['codespe']);
            $sheet->setCellValue('B' . $rowNumber, $row['nombrees']);
            $sheet->setCellValue('C' . $rowNumber, $row['fecha_create']);
            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="especialidades.xlsx"');
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
    <title>Reporte de Especialidades</title>
</head>
<body>
    <h1>Reporte de Especialidades</h1>
    <form method="post">
        <button type="submit" name="export" value="pdf" class="btn btn-warning">Exportar a PDF</button>
        <button type="submit" name="export" value="excel" class="btn btn-success">Exportar a Excel</button>
    </form>
</body>
</html>
