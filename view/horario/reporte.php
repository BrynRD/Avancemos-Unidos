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
                $this->Cell(95,10,'Lista de los horarios',1,0,'C');
                $this->Ln(20);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
            }
        }

        $result = mysqli_query($connString, "SELECT horario.codhor, horario.nomhor, doctor.coddoc, doctor.dnidoc, doctor.nomdoc, doctor.apedoc, doctor.correo, horario.fere FROM horario INNER JOIN doctor ON horario.coddoc = doctor.coddoc") or die("database error:". mysqli_error($connString));

        $pdf = new PDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial','B',10);
        $w = array(10, 55, 150, 65);

        $pdf->Cell($w[0],12,'#',1);
        $pdf->Cell($w[1],12,'HORARIOS',1);
        $pdf->Cell($w[2],12,'MEDICOS',1);
        $pdf->Cell($w[3],12,'FECHA',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);

        foreach($result as $row) {
            $pdf->Cell($w[0],6,$row['codhor'],1);
            $pdf->Cell($w[1],6,utf8_decode($row['nomhor']),1);
            $pdf->Cell($w[2],6,utf8_decode($row['nomdoc']),1);
            $pdf->Cell($w[3],6,$row['fere'],1);
            $pdf->Ln();
        }

        $pdf->Output('horarios.pdf', 'D');
        exit;

    } elseif ($exportType === 'excel') {
        // Exportar a Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'Horarios');
        $sheet->setCellValue('C1', 'Medicos');
        $sheet->setCellValue('D1', 'Fecha');

        $result = mysqli_query($connString, "SELECT horario.codhor, horario.nomhor, doctor.coddoc, doctor.dnidoc, doctor.nomdoc, doctor.apedoc, doctor.correo, horario.fere FROM horario INNER JOIN doctor ON horario.coddoc = doctor.coddoc") or die("database error:". mysqli_error($connString));

        $rowNumber = 2;
        while ($row = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A' . $rowNumber, $row['codhor']);
            $sheet->setCellValue('B' . $rowNumber, $row['nomhor']);
            $sheet->setCellValue('C' . $rowNumber, $row['nomdoc']);
            $sheet->setCellValue('D' . $rowNumber, $row['fere']);
            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="horarios.xlsx"');
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
    <title>Reporte de Horarios</title>
</head>
<body>
    <h1>Reporte de Horarios</h1>
    <form method="post">
        <button type="submit" name="export" value="pdf" class="btn btn-warning">Exportar a PDF</button>
        <button type="submit" name="export" value="excel" class="btn btn-success">Exportar a Excel</button>
    </form>
</body>
</html>
