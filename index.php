
<?php
require_once(__DIR__ . '/classes/processor.php');
use mod_smartspe\processor;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload .xlsx File</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9fafb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .upload-box {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      text-align: center;
      width: 350px;
    }
    h2 {
      margin-bottom: 20px;
      color: #333;
    }
    input[type="file"] {
      display: block;
      margin: 15px auto;
      padding: 10px;
    }
    .btn {
      background: #2563eb;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
    }
    .btn:hover {
      background: #1e40af;
    }
    .message {
      margin-top: 20px;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="upload-box">
    <h2>Upload Excel File (.xlsx)</h2>
    <form action="index.php" method="post" enctype="multipart/form-data">
      <input type="file" name="myfile" accept=".xlsx" required>
      <button type="submit" class="btn">Upload</button>
    </form>
    <?php if (!empty($message)): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
  </div>
</body>
</html>

<?php

$processor = new processor();

if (isset($_POST['submit']))
{
    if($_FILES['input_file'][''] == "")
    {
        $message = "⚠️ Error uploading file. Error code: " . $file["error"];
    }
    else
    {
        $file = $_FILES['input_file'];
        $processor->upload_file($file); //Passing file to process
    }
}