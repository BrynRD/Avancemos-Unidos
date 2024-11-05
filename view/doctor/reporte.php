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
                $this->Cell(95,10,'Lista de los doctores',1,0,'C');
                $this->Ln(20);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
            }
        }

        $result = mysqli_query($connString, "SELECT doctor.coddoc, doctor.dnidoc, doctor.nomdoc, doctor.apedoc, specialty.nombrees, doctor.telefo, doctor.sexo, doctor.fechanaci, doctor.naciona FROM doctor INNER JOIN specialty ON doctor.codespe = specialty.codespe") or die("database error:". mysqli_error($connString));

        $pdf = new PDF('L','mm','A4');
        $pdf->AddPage();
        $pdf->AliasNbPages();
        $pdf->SetFont('Arial','B',12);
        $w = array(10, 25, 50, 50, 45, 25, 25, 30, 30);

        $pdf->Cell($w[0],12,'#',1);
        $pdf->Cell($w[1],12,'DNI',1);
        $pdf->Cell($w[2],12,'NOMBRES',1);
        $pdf->Cell($w[3],12,'APELLIDOS',1);
        $pdf->Cell($w[4],12,'ESPECIALIDAD',1);
        $pdf->Cell($w[5],12,'TELEFONO',1);
        $pdf->Cell($w[6],12,'SEXO',1);
        $pdf->Cell($w[7],12,'NACIMIENTO',1);
        $pdf->Cell($w[8],12,'NACIONALIDAD',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',12);

        foreach($result as $row) {
            $pdf->Cell($w[0],6,$row['coddoc'],1);
            $pdf->Cell($w[1],6,utf8_decode($row['dnidoc']),1);
            $pdf->Cell($w[2],6,utf8_decode($row['nomdoc']),1);
            $pdf->Cell($w[3],6,utf8_decode($row['apedoc']),1);
            $pdf->Cell($w[4],6,utf8_decode($row['nombrees']),1);
            $pdf->Cell($w[5],6,utf8_decode($row['telefo']),1);
            $pdf->Cell($w[6],6,utf8_decode($row['sexo']),1);
            $pdf->Cell($w[7],6,utf8_decode($row['fechanaci']),1);
            $pdf->Cell($w[8],6,utf8_decode($row['naciona']),1);
            $pdf->Ln();
        }

        $pdf->Output('doctores.pdf', 'D');
        exit;
    } elseif ($exportType === 'excel') {
        // Exportar a Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'DNI');
        $sheet->setCellValue('C1', 'Nombres');
        $sheet->setCellValue('D1', 'Apellidos');
        $sheet->setCellValue('E1', 'Especialidad');
        $sheet->setCellValue('F1', 'Telefono');
        $sheet->setCellValue('G1', 'Sexo');
        $sheet->setCellValue('H1', 'Nacimiento');
        $sheet->setCellValue('I1', 'Nacionalidad');

        $result = mysqli_query($connString, "SELECT doctor.coddoc, doctor.dnidoc, doctor.nomdoc, doctor.apedoc, specialty.nombrees, doctor.telefo, doctor.sexo, doctor.fechanaci, doctor.naciona FROM doctor INNER JOIN specialty ON doctor.codespe = specialty.codespe") or die("database error:". mysqli_error($connString));

        $rowNumber = 2;
        while ($row = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A' . $rowNumber, $row['coddoc']);
            $sheet->setCellValue('B' . $rowNumber, $row['dnidoc']);
            $sheet->setCellValue('C' . $rowNumber, utf8_encode($row['nomdoc']));
            $sheet->setCellValue('D' . $rowNumber, utf8_encode($row['apedoc']));
            $sheet->setCellValue('E' . $rowNumber, utf8_encode($row['nombrees']));
            $sheet->setCellValue('F' . $rowNumber, $row['telefo']);
            $sheet->setCellValue('G' . $rowNumber, $row['sexo']);
            $sheet->setCellValue('H' . $rowNumber, $row['fechanaci']);
            $sheet->setCellValue('I' . $rowNumber, utf8_encode($row['naciona']));
            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="doctores.xlsx"');
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
    <title>Reporte de Doctores</title>
</head>
<body>
    <h1>Reporte de Doctores</h1>
    <form method="post">
        <button type="submit" name="export" value="pdf" class="btn btn-warning">Exportar a PDF</button>
        <button type="submit" name="export" value="excel" class="btn btn-success">Exportar a Excel</button>
    </form>
</body>
</html>
