<?php

session_start();
include('../dbcon.php');
header('Content-Type: application/json');

$uploadDir = '../receipts/scanned_receipts/'; 

// Save the file with formatted file name
$dateName = date('Y-m-d');
$originalName = basename($_FILES['receipt_file']['name']);
$filename = $uploadDir . $dateName . '_' . $originalName;

if (!isset($_FILES['receipt_file']) || !move_uploaded_file($_FILES['receipt_file']['tmp_name'] ?? '', $filename)) {
    echo json_encode(['error' => 'File upload failed']);
    exit;
}

// ---------------------
// 1️⃣ OCR using OCR.Space
// ---------------------
$ocrApiKey = 'K88257033088957'; // Your OCR.Space API key
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// OCR.Space parameters
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'apikey' => $ocrApiKey,
    'language' => 'eng',
    'isOverlayRequired' => 'false',
    'file' => new CURLFile($filename)
]);

$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(['error' => 'OCR API request failed', 'details' => curl_error($ch)]);
    exit;
}
curl_close($ch);

// Decode OCR API response
$ocrData = json_decode($response, true);
if (!$ocrData || empty($ocrData['ParsedResults'][0]['ParsedText'])) {
    echo json_encode(['error' => 'We could not read any text from the image. Please make sure the receipt is clear, well-lit, and not blurry, then try again.', 'raw_response' => $response]);
    exit;
}

$ocrText = $ocrData['ParsedResults'][0]['ParsedText'];

// ---------------------
// 2️⃣ Gemini Prompt
// ---------------------
$prompt = 'Extract the following fields from the receipt text as a JSON object (not an array):
- date: Format as YYYY-MM-DD.
- store: Store name in sentence case (capitalize first letter of each word).
- items: An array of objects, each with "item_name" (in sentence case) and "item_amount" (as a number).
- total_amount: The total amount due (look for terms like "total", "total amount", "amount due") as a number.
- payment_method: In title case (e.g., "Cash", "Credit Card").
- invoice_number: The invoice or receipt number.
Return a single JSON object. If a field is missing, return an empty string or empty array for items.
Make sure that it is a receipt, a valid receipt (having sales invoice, total amount to pay, items) and if not, just say that it is not a valid receipt.
Here is the receipt text:\n\n' . $ocrText;

// Gemini API Call
$apiKey = 'AIzaSyA0zN3fWvqKYA5vaf3295to7S1SoIrB1L4'; 
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$geminiResponse = curl_exec($ch);
if ($geminiResponse === false) {
    echo json_encode(['error' => 'Gemini API request failed', 'details' => curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Decode Gemini response
$data = json_decode($geminiResponse, true);
if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    file_put_contents('gemini_debug.txt', $geminiResponse); // writes to same folder as PHP file
    echo json_encode(['error' => 'Gemini response invalid', 'raw_response' => $geminiResponse]);
    exit;
}

$structured = trim($data['candidates'][0]['content']['parts'][0]['text']);
$structured = trim($structured, "```json \n");
$structured = preg_replace('/^```json\s*/i', '', $structured);
$structured = preg_replace('/^```/', '', $structured);
$structured = preg_replace('/```$/', '', $structured);
$structured = trim($structured);

// ---------------------
// 3️⃣ Return JSON
// ---------------------
echo json_encode([
    'ocr_text' => $ocrText,
    'structured' => $structured
]);
