<?php
include "../includes/db.php";
session_start();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $contactDate = $_POST['contact_date'] ?? '';
    $contactTime = $_POST['contact_time'] ?? '';
    $contactPeriod = $_POST['contact_period'] ?? 'AM';
    $inquiryType = $_POST['inquiry_type'] ?? [];
    $otherType = trim($_POST['other_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($firstName) || empty($lastName)) {
        $error = 'Please provide both first and last name.';
    } elseif (!$email) {
        $error = 'Please provide a valid email address.';
    } elseif (empty($message)) {
        $error = 'Please provide a message description.';
    } else {
        // Combine first and last name
        $fullName = $firstName . ' ' . $lastName;

        // Combine inquiry types
        $inquiryTypes = implode(', ', $inquiryType);
        if (!empty($otherType) && in_array('others', $inquiryType)) {
            $inquiryTypes .= ' (' . $otherType . ')';
        }

        // Add contact preference to message if provided
        $fullMessage = $message;
        if (!empty($contactDate) || !empty($contactTime)) {
            $fullMessage .= "\n\nPreferred Contact Time: " . $contactDate . ' ' . $contactTime . ' ' . $contactPeriod;
        }
        if (!empty($inquiryTypes)) {
            $fullMessage .= "\nInquiry Type: " . $inquiryTypes;
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO inquiries(name, email, company, message) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullName, $email, $company, $fullMessage);

        if ($stmt->execute()) {
            $success = true;
            // Clear form data
            $_POST = [];
        } else {
            $error = 'Failed to submit your inquiry. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NexGen Solution</title>

    <!-- Google Fonts Link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS Link -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Inter", sans-serif;
    }

    html,
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .page-wrapper {
        min-height: 100vh;
        padding: 2rem 1rem;
    }

    .back-button {
        display: inline-block;
        margin-bottom: 2rem;
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .back-button:hover {
        color: #ffd700;
    }

    .form-container {
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        padding: 2.5rem;
        max-width: 700px;
        margin: 0 auto;
    }

    .form-title {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .form-subtitle {
        color: #6c757d;
        margin-bottom: 2rem;
        font-size: 1rem;
        text-align: center;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.6rem;
        font-size: 0.95rem;
    }

    .form-control,
    .form-select,
    .form-check-input {
        border: 1px solid #d4d4d4;
        padding: 0.75rem;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .input-group-text {
        background-color: #f8f9fa;
        border: 1px solid #d4d4d4;
        color: #6c757d;
    }

    .form-check {
        margin-bottom: 0.75rem;
    }

    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
    }

    .alert {
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.8rem 2.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 1rem;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 0.5rem;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .time-input-group {
        display: grid;
        grid-template-columns: 1fr 1fr 0.8fr;
        gap: 0.75rem;
        align-items: flex-end;
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .form-check-input {
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .form-container {
            padding: 1.75rem;
        }

        .form-title {
            font-size: 1.5rem;
        }

        .form-subtitle {
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .time-input-group {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .page-wrapper {
            padding: 1rem 0.5rem;
        }

        .form-container {
            padding: 1.25rem;
            border-radius: 8px;
        }

        .form-title {
            font-size: 1.25rem;
        }

        .form-subtitle {
            font-size: 0.9rem;
        }

        .form-label {
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            padding: 0.65rem;
            font-size: 0.9rem;
        }

        .btn-submit {
            padding: 0.7rem 1.5rem;
            font-size: 0.95rem;
        }
    }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <div class="form-container">
            <h1 class="form-title">Get in touch</h1>
            <p class="form-subtitle">Have questions about NexGen? We're here to help.</p>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>
                <strong>Success!</strong> Your inquiry has been submitted successfully. We will contact you soon.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form method="post" action="" id="contactForm">
                <!-- Full Name -->
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">Email Address *</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" placeholder="example@company.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-telephone"></i>
                        </span>
                        <input type="tel" class="form-control" name="phone" placeholder="(000) 000-0000"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <!-- Company -->
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-building"></i>
                        </span>
                        <input type="text" name="company" class="form-control" placeholder="Company Name"
                            value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                    </div>
                </div>

                <!-- Best time to contact -->
                <div class="mb-3">
                    <label class="form-label">Best Time to Contact</label>
                    <div class="time-input-group">
                        <input type="date" name="contact_date" class="form-control"
                            value="<?= htmlspecialchars($_POST['contact_date'] ?? '') ?>">
                        <input type="time" name="contact_time" class="form-control"
                            value="<?= htmlspecialchars($_POST['contact_time'] ?? '') ?>">
                        <select name="contact_period" class="form-select">
                            <option value="AM"
                                <?= (isset($_POST['contact_period']) && $_POST['contact_period'] === 'AM') ? 'selected' : '' ?>>
                                AM</option>
                            <option value="PM"
                                <?= (isset($_POST['contact_period']) && $_POST['contact_period'] === 'PM') ? 'selected' : 'selected' ?>>
                                PM</option>
                        </select>
                    </div>
                </div>

                <!-- Inquiry Type -->
                <div class="mb-3">
                    <label class="form-label">Inquiry Type</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="General Inquiry" id="type1"
                                <?= (isset($_POST['inquiry_type']) && in_array('General Inquiry', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type1"> &nbsp; General Inquiry</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="Service Request" id="type2"
                                <?= (isset($_POST['inquiry_type']) && in_array('Service Request', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type2"> &nbsp; Service Request</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="Support"
                                id="type3"
                                <?= (isset($_POST['inquiry_type']) && in_array('Support', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type3"> &nbsp; Support</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="others"
                                id="type4"
                                <?= (isset($_POST['inquiry_type']) && in_array('others', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type4"> &nbsp; Others</label>
                        </div>
                    </div>
                </div>

                <!-- If Others -->
                <div class="mb-3" id="otherTypeField" style="display: none;">
                    <label class="form-label">Please Specify</label>
                    <input type="text" name="other_type" class="form-control" placeholder="Please describe..."
                        value="<?= htmlspecialchars($_POST['other_type'] ?? '') ?>">
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="form-label">Description of Problem *</label>
                    <textarea name="message" class="form-control" rows="5"
                        placeholder="Please describe your inquiry or problem in detail..."
                        required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">
                    <i class="bi bi-send"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show/hide "If Others" field based on checkbox
    document.getElementById('type4').addEventListener('change', function() {
        document.getElementById('otherTypeField').style.display = this.checked ? 'block' : 'none';
    });

    // Check on page load if "Others" is already checked
    if (document.getElementById('type4').checked) {
        document.getElementById('otherTypeField').style.display = 'block';
    }
    </script>
</body>

</html>