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
        $stmt = $conn->prepare("SELECT o.id, o.first_name, o.last_name, o.email, o.phone, o.subtotal, o.total, o.address, 
                                       p.product_name, p.gst_rate, p.shipping_cost, o.quantity, o.price, o.order_date
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
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $logoPath = '../images/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 10, 40); // Increased size slightly
        }

        // Brand Name
        $this->SetXY(10, 25);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(15, 23, 42); // Primary Dark
        // $this->Cell(40, 5, 'CONSTRUCTO', 0, 0, 'C'); // Optional if logo has text

        // Company Details (Right Aligned)
        $this->SetXY(100, 10);
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 10, 'INVOICE', 0, 1, 'R');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 116, 139); // Slate 500
        $this->Cell(0, 5, 'Constructo Pvt Ltd.', 0, 1, 'R');
        $this->Cell(0, 5, 'Changanacherry, Kottayam, 686101', 0, 1, 'R');
        $this->Cell(0, 5, 'contact@constructo.com | +91 81716151', 0, 1, 'R');

        $this->Ln(15);
        $this->SetDrawColor(226, 232, 240); // Light border
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-30);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);

        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(148, 163, 184); // Slate 400
        $this->Cell(0, 5, 'Thank you for choosing Constructo!', 0, 1, 'C');
        $this->Cell(0, 5, 'For support, visit www.constructo.com/support', 0, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// Customer & Invoice Details Section
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
$y_start_address = $y;
$pdf->MultiCell(90, 6, $customer_info['name'] . "\n" . $customer_info['address'] . "\n" . $customer_info['phone'] . "\n" . $customer_info['email'], 0, 'L');

// Column 2: Details
$pdf->SetXY($x + 95, $y);
$pdf->Cell(40, 6, 'Date:', 0, 0);
$pdf->Cell(50, 6, $customer_info['date'], 0, 1);
$pdf->SetX($x + 95);
$pdf->Cell(40, 6, 'Invoice No:', 0, 0);
$invoice_no = isset($order_ids[0]) ? 'INV-' . str_pad($order_ids[0], 6, '0', STR_PAD_LEFT) : 'INV-0000';
$pdf->Cell(50, 6, $invoice_no, 0, 1);
$pdf->SetX($x + 95);
$pdf->Cell(40, 6, 'Order Refs:', 0, 0);
$pdf->Cell(50, 6, implode(', ', $order_ids), 0, 1);

// Move to next section
$pdf->SetY(max($pdf->GetY(), $y_start_address + 30) + 10);

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

$final_grand_total = 0;
$final_subtotal = 0;
$final_gst = 0;
$final_shipping = 0;

foreach ($order_details as $item) {
    // Logic extraction matches checkout.php
    $item_subtotal = $item['subtotal']; // Price * Qty
    $item_total = $item['total'];       // Subtotal + GST + Shipping

    // If total (new schema) is missing/0, fallback to legacy calculation
    if ($item_total == 0 || $item_total == null) {
        $gst_rate = isset($item['gst_rate']) ? $item['gst_rate'] : 18;
        $shipping_unit = isset($item['shipping_cost']) ? $item['shipping_cost'] : 50;

        $calc_gst = $item_subtotal * ($gst_rate / 100);
        $calc_shipping = $shipping_unit * $item['quantity'];

        $item_total = $item_subtotal + $calc_gst + $calc_shipping;
        $final_gst += $calc_gst;
        $final_shipping += $calc_shipping;
    } else {
        // Calculate components back from the stored total if possible, or use current product rates
        // Best approach: Use product rates to estimate split, but ensure sum matches Total
        $gst_rate = isset($item['gst_rate']) ? $item['gst_rate'] : 18;
        $shipping_unit = isset($item['shipping_cost']) ? $item['shipping_cost'] : 50;

        // Use current rates (as stored in products) 
        // Note: This assumes rates haven't changed. Ideal is storing in orders table.
        $calc_gst = $item_subtotal * ($gst_rate / 100);
        $calc_shipping = $shipping_unit * $item['quantity'];

        $final_gst += $calc_gst;
        $final_shipping += $calc_shipping;
    }

    $final_subtotal += $item_subtotal;
    // Trust the order total for the grand sum
    $final_grand_total += $item_total;

    $pdf->Cell(15, 10, $i++, 'B', 0, 'C');
    $pdf->Cell(85, 10, substr($item['product_name'], 0, 45) . (strlen($item['product_name']) > 45 ? '...' : ''), 'B', 0, 'L');
    $pdf->Cell(30, 10, number_format($item['price'], 2), 'B', 0, 'R');
    $pdf->Cell(20, 10, $item['quantity'], 'B', 0, 'C');
    $pdf->Cell(40, 10, number_format($item['total'], 2), 'B', 1, 'R');
}

$pdf->Ln(5);

// Totals
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(150, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($final_subtotal, 2), 0, 1, 'R');

$pdf->Cell(150, 8, 'GST (Included):', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($final_gst, 2), 0, 1, 'R');

$pdf->Cell(150, 8, 'Shipping:', 0, 0, 'R');
$pdf->Cell(40, 8, number_format($final_shipping, 2), 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(150, 12, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(40, 12, 'Rs. ' . number_format($final_grand_total, 2), 0, 1, 'R');

$pdf->Output('D', 'Invoice_' . date('Ymd') . '.pdf');
?>