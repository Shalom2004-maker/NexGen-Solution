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

    if (empty($firstName) || empty($lastName)) {
        $error = 'Please provide both first and last name.';
    } elseif (!$email) {
        $error = 'Please provide a valid email address.';
    } elseif (empty($message)) {
        $error = 'Please provide a message description.';
    } else {
        $fullName = $firstName . ' ' . $lastName;
        $inquiryTypes = implode(', ', $inquiryType);

        if (!empty($otherType) && in_array('others', $inquiryType, true)) {
            $inquiryTypes .= ' (' . $otherType . ')';
        }

        $fullMessage = $message;
        if (!empty($contactDate) || !empty($contactTime)) {
            $fullMessage .= "\n\nPreferred Contact Time: " . $contactDate . ' ' . $contactTime . ' ' . $contactPeriod;
        }
        if (!empty($inquiryTypes)) {
            $fullMessage .= "\nInquiry Type: " . $inquiryTypes;
        }

        $stmt = $conn->prepare("INSERT INTO inquiries(name, email, company, message) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullName, $email, $company, $fullMessage);

        if ($stmt->execute()) {
            $success = true;
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Orbitron:wght@500;700&display=swap"
        rel="stylesheet">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/ui-universal.css" rel="stylesheet">

    <script src="../js/jquery.js"></script>
    <script src="../js/validate.js"></script>
    <script src="../js/future-ui.js" defer></script>
</head>

<body class="future-page future-contact" data-theme="nebula">
    <div class="future-grid" aria-hidden="true"></div>
    <div class="future-orb future-orb-a" aria-hidden="true"></div>
    <div class="future-orb future-orb-b" aria-hidden="true"></div>
    <div class="future-orb future-orb-c" aria-hidden="true"></div>

    <div class="theme-float">
        <div class="theme-switcher neo-panel" role="group" aria-label="Theme switcher">
            <span class="theme-switcher-label">Theme</span>
            <button class="theme-chip pressable is-active" type="button" data-theme-choice="nebula"
                aria-pressed="true">Nebula</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="ember"
                aria-pressed="false">Ember</button>
            <button class="theme-chip pressable" type="button" data-theme-choice="aurora"
                aria-pressed="false">Aurora</button>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="page-nav">
            <a href="index.php" class="back-button pressable" data-tilt="7">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="form-container tilt-surface" data-tilt="6">
            <h1 class="form-title">Get In Touch</h1>
            <p class="form-subtitle">Have questions about NexGen? We are here to help.</p>

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

            <form method="post" action="" id="contactForm" class="w-100">
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 form-field">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name"
                                data-validation="required min-length max-length" data-min-length="2"
                                data-max-length="50" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                            <div id="first_name_error" class="validation-error"></div>
                        </div>
                        <div class="col-12 col-sm-6 form-field">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name"
                                data-validation="required min-length max-length" data-min-length="2"
                                data-max-length="50" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                            <div id="last_name_error" class="validation-error"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 form-field">
                    <label class="form-label">Email Address *</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="text" class="form-control" name="email" data-validation="required email"
                            placeholder="example@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div id="email_error" class="validation-error"></div>
                </div>

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

                <div class="mb-3">
                    <label class="form-label">Best Time To Contact</label>
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
                                <?= (!isset($_POST['contact_period']) || $_POST['contact_period'] === 'PM') ? 'selected' : '' ?>>
                                PM</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Inquiry Type</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="General Inquiry" id="type1"
                                <?= (isset($_POST['inquiry_type']) && in_array('General Inquiry', $_POST['inquiry_type'], true)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type1">General Inquiry</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="Service Request" id="type2"
                                <?= (isset($_POST['inquiry_type']) && in_array('Service Request', $_POST['inquiry_type'], true)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type2">Service Request</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="Support"
                                id="type3"
                                <?= (isset($_POST['inquiry_type']) && in_array('Support', $_POST['inquiry_type'], true)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type3">Support</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="others"
                                id="type4"
                                <?= (isset($_POST['inquiry_type']) && in_array('others', $_POST['inquiry_type'], true)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type4">Others</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="otherTypeField" style="display: none;">
                    <label class="form-label">Please Specify</label>
                    <input type="text" name="other_type" class="form-control" placeholder="Please describe..."
                        value="<?= htmlspecialchars($_POST['other_type'] ?? '') ?>">
                </div>

                <div class="mb-4 form-field">
                    <label class="form-label">Description Of Problem *</label>
                    <textarea name="message" class="form-control" rows="5"
                        data-validation="required min-length max-length" data-min-length="10" data-max-length="2000"
                        placeholder="Please describe your inquiry or problem in detail..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <div id="message_error" class="validation-error"></div>
                </div>

                <button type="submit" class="btn-submit pressable" data-tilt="8">
                    <i class="bi bi-send"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        var otherTypeCheckbox = document.getElementById("type4");
        var otherTypeField = document.getElementById("otherTypeField");

        if (!otherTypeCheckbox || !otherTypeField) {
            return;
        }

        function syncOtherType() {
            otherTypeField.style.display = otherTypeCheckbox.checked ? "block" : "none";
        }

        otherTypeCheckbox.addEventListener("change", syncOtherType);
        syncOtherType();
    })();
    </script>
</body>

</html>
