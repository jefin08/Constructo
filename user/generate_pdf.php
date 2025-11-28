<?php
session_start();
require('fpdf/fpdf.php');
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_ids = isset($_POST['order_ids']) ? $_POST['order_ids'] : [];

if (empty($order_ids)) {
    die("No orders selected.");
}

// Fetch Data
$order_details = [];
$total_amount = 0;
$customer_info = null;

try {
    foreach ($order_ids as $order_id) {
        $stmt = $conn->prepare("SELECT o.id, o.first_name, o.last_name, o.email, o.phone, o.subtotal, o.address, 
                                       p.product_name, o.quantity, o.price, o.order_date
                                FROM orders o
                                JOIN products p ON o.product_id = p.id
                                WHERE o.id = :id AND o.client_id = :uid");
        $stmt->execute([':id' => $order_id, ':uid' => $user_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $order_details[] = $row;
            $total_amount += $row['subtotal'];

            if ($customer_info === null) {
                $customer_info = [
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'date' => date('d M Y', strtotime($row['order_date']))
                ];
            }
        }
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

class PDF extends FPDF {
    function Header() {
        // Logo
        if(file_exists('logo.png')) {
            $this->Image('logo.png', 10, 10, 30);
        }
        
        // Company Info
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(15, 23, 42); // Dark Blue
        $this->Cell(0, 10, 'CONSTRUCTO', 0, 1, 'R');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 116, 139); // Gray
        $this->Cell(0, 5, 'Changanacherry, Kottayam, 686101', 0, 1, 'R');
        $this->Cell(0, 5, 'constructo@gmail.com | +91 81716151', 0, 1, 'R');
        
        $this->Ln(15);
        
        // Invoice Title
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(245, 158, 11); // Accent Orange
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'L');
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');
        $this->Cell(0, 5, 'For inquiries, contact support@constructo.com', 0, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// Customer & Invoice Details
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(95, 7, 'Bill To:', 0, 0);
$pdf->Cell(95, 7, 'Invoice Details:', 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(51, 65, 85);

// Column 1: Customer
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(90, 6, $customer_info['name'] . "\n" . $customer_info['address'] . "\n" . $customer_info['phone'] . "\n" . $customer_info['email'], 0, 'L');

// Column 2: Details
$pdf->SetXY($x + 95, $y);
$pdf->Cell(40, 6, 'Date:', 0, 0);
$pdf->Cell(50, 6, $customer_info['date'], 0, 1);
$pdf->SetX($x + 95);
$pdf->Cell(40, 6, 'Order Count:', 0, 0);
$pdf->Cell(50, 6, count($order_ids), 0, 1);

$pdf->Ln(15);

// Table Header
$pdf->SetFillColor(241, 245, 249); // Light Gray
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(15, 10, '#', 0, 0, 'C', true);
$pdf->Cell(85, 10, 'Product', 0, 0, 'L', true);
$pdf->Cell(30, 10, 'Price', 0, 0, 'R', true);
$pdf->Cell(20, 10, 'Qty', 0, 0, 'C', true);
$pdf->Cell(40, 10, 'Total', 0, 1, 'R', true);

// Table Body
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(51, 65, 85);
$i = 1;

foreach ($order_details as $item) {
    $pdf->Cell(15, 10, $i++, 'B', 0, 'C');
    $pdf->Cell(85, 10, $item['product_name'], 'B', 0, 'L');
    $pdf->Cell(30, 10, number_format($item['price'], 2), 'B', 0, 'R');
    $pdf->Cell(20, 10, $item['quantity'], 'B', 0, 'C');
    $pdf->Cell(40, 10, number_format($item['subtotal'], 2), 'B', 1, 'R');
}

// Calculations
$shipping = count($order_ids) > 0 ? 59 : 0;
$gst = $total_amount * 0.10;
$grand_total = $total_amount + $gst + $shipping;

$pdf->Ln(5);

// Totals
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(150, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($total_amount, 2), 0, 1, 'R');

$pdf->Cell(150, 8, 'GST (10%):', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($gst, 2), 0, 1, 'R');

$pdf->Cell(150, 8, 'Shipping:', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($shipping, 2), 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(150, 12, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(40, 12, 'Rs. ' . number_format($grand_total, 2), 0, 1, 'R');

$pdf->Output('D', 'Invoice_' . date('Ymd') . '.pdf');
?>
