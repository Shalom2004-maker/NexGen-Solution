<link rel="stylesheet" href="../css/bootstrap.min.css">
<script src="../js/bootstrap.bundle.js"></script>
<script src="../js/validate.js"></script>

<body>

    <form method="get" class="mt-5 container border rounded shadow p-4 w-75 bg-light-subtle">
        <button class="d-flex justify-content-end end-0 btn btn-primary mb-3 mx-auto">
            <a href="check_hash.php" class="nav-link">Check Hash</a>
        </button>

        <input type="text" class="form-control mb-3" name="hash" data-validation="required" required
            placeholder="Enter password to hash..">
        <div id="hash_error" class="text-danger validation-error mb-2"></div>
        <button type="submit" class="btn btn-info">Hash</button>

        <?php
        if (isset($_GET['hash'])) {
            $hash = password_hash($_GET['hash'], PASSWORD_BCRYPT); ?>
        <div class="alert alert-success mt-3">
            <strong>Hashed Password:</strong> <?php echo $hash; ?>
        </div>
        <?php
        }
        ?>
    </form>
</body>
