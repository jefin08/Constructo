<?php
include 'db_connect.php';

// Define rates for categories/keywords
$upgrades = [
    // Cement: 28% GST, Heavy shipping
    ['Cement', 28, 50],
    ['Concrete', 28, 50],

    // Steel/TMT: 18% GST (already there), Heavy shipping (Kg)
    ['TMT', 18, 5],
    ['Steel', 18, 10],

    // Paints: 18% GST, Medium shipping
    ['Paint', 18, 40],
    ['Putty', 18, 20],
    ['Primer', 18, 30],

    // Sand/Aggregates: 5% GST, Very Heavy -> Bulk?
    ['Sand', 5, 20],
    ['Aggregate', 5, 15],
    ['Bricks', 5, 2], // Per brick? Usually 12, but per piece low shipping
    ['Blocks', 12, 5],

    // Electricals: 18%, Light
    ['Wire', 18, 5],
    ['Switch', 18, 2],
    ['Socket', 18, 2],
    ['Fan', 18, 50],
    ['Bulb', 18, 1],
    ['Tube', 18, 10],

    // Plumbing: 18%, Medium/Variable
    ['Pipe', 18, 15],
    ['Tap', 18, 30],
    ['Commode', 18, 200], // Heavy
    ['Basin', 18, 100],
    ['Tank', 18, 500], // Huge

    // Tiles/Stone: 18%, Heavy
    ['Tiles', 18, 25], // Per sq ft? or box? Usually box. Let's say per unit if unit is sq ft
    ['Granite', 18, 30],
    ['Marble', 18, 30],

    // Plywood/Wood: 18%, bulky
    ['Plywood', 18, 40],
    ['Adhesive', 18, 15], // Fevicol

    // Tools: 18%, Medium
    ['Drill', 18, 50],
    ['Grinder', 18, 40],
    ['Hammer', 18, 20],
    ['Tape', 18, 5],

    // Safety: 18%, Light
    ['Helmet', 18, 20],
    ['Shoes', 18, 50],
    ['Jacket', 18, 10],
    ['Gloves', 5, 5], // Often lower tax
    ['Mask', 12, 2],

    // Hardware: 18%
    ['Lock', 18, 20],
    ['Hinge', 18, 5],
    ['Handle', 18, 10],
    ['Screw', 18, 5], // Pkt
    ['Nail', 18, 10], // Kg

    // Default Fallback
    ['Default', 18, 20]
];

// Fetch all products
$stmt = $conn->query("SELECT id, product_name FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    $name = $p['product_name'];
    $gst = 18;
    $shipping = 20;

    foreach ($upgrades as $rule) {
        if ($rule[0] === 'Default')
            continue;
        if (stripos($name, $rule[0]) !== false) {
            $gst = $rule[1];
            $shipping = $rule[2];
            break; // Stop at first match (basic heuristic)
        }
    }

    // Update
    $update = $conn->prepare("UPDATE products SET gst_rate = :gst, shipping_cost = :ship WHERE id = :id");
    $update->execute([':gst' => $gst, ':ship' => $shipping, ':id' => $p['id']]);
    echo "Updated {$name}: GST {$gst}%, Shipping ₹{$shipping}<br>";
}

echo "Done updating rates.";
?>