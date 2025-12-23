<?php
// api/test_delete_survey.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delete Survey (Test)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Delete Survey (API Test)</h2>

        <?php
        $response = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['id'])) {
            $token = trim($_POST['token']);
            $id = intval($_POST['id']);

            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\')
                . '/surveys.php?action=delete&id=' . urlencode($id);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true); // DELETE uses POST in your API
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        ?>

        <form method="post" class="mb-3">
            <div class="mb-3">
                <label class="form-label">API Token</label>
                <input type="text" name="token" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Survey ID to Delete</label>
                <input type="number" name="id" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-danger">Delete Survey</button>
        </form>

        <?php if ($response): ?>
            <div class="card">
                <div class="card-header">API Response</div>
                <div class="card-body">
                    <pre><?= htmlspecialchars($response) ?></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
