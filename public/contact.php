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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f5f5f5;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .form-container {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
        margin: 40px auto;
        max-width: 900px;
    }

    .form-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .form-subtitle {
        color: #6c757d;
        margin-bottom: 30px;
        font-size: 1rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .btn-submit {
        background-color: #28a745;
        border-color: #28a745;
        padding: 12px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
    }

    .time-input-group {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .alert {
        border-radius: 5px;
        margin-bottom: 25px;
    }

    button.btn {
        background-color: #337ccfe2;
        color: white;
        font-weight: bold;
    }

    button.btn:hover {
        background-color: #337ccfe2;
        color: white;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="btn" style="margin-top: 2rem; color: #3793dfe2; font-weight: 600;">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                fill="#3793dfe2">
                <path
                    d="m480-320 56-56-64-64h168v-80H472l64-64-56-56-160 160 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
            </svg>&nbsp;&nbsp;Back to Home</a>
        <div class="form-container mt-4 mb-4 w-50">
            <center>
                <h1 class="form-title">Get in touch</h1>
                <p class="form-subtitle">
                    Have questions about NexGen? We're here to help.
                </p>
            </center>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> Your inquiry has been submitted successfully. We will contact you soon.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form method="post" action="" id="contactForm">
                <!-- Full Name -->
                <div class="mb-4">
                    <label class="form-label">Full Name:</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="form-label">Email:</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="inputGroupPrepend">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px"
                                fill="lightslategray">
                                <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83
                                     0 156 31.5T763-763q54 54 85.5 127T880-480v58q0 59-40.5 100.5T740-280q-35 0-66-15t-52-43q-29 29-65.5 43.5T480-280q-83
                                      0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480v58q0 26 17 44t43 18q26 0 
                                      43-18t17-44v-58q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93h200v80H480Zm0-280q50 0 
                                      85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z" />
                            </svg>
                        </span>
                        <input type="text" class="form-control" id="validationCustomUsername" name="email"
                            placeholder="ex: myname@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            aria-describedby="inputGroupPrepend" required>
                    </div>
                </div>

                <!-- Phone Number -->
                <div class="mb-4">
                    <label class="form-label">Phone Number: </label>
                    <div class="input-group">
                        <span class="input-group-text" id="inputGroupPrepend">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                                fill="lightslategray">
                                <path d=" M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14
                            0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72
                            48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30
                            12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67
                            67ZM241-600Zm358 358Z" />
                            </svg>
                        </span>
                        <input type="tel" class="form-control" id="validationCustomUsername" name="phone"
                            placeholder="(000) 000-0000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            aria-describedby="inputGroupPrepend" required>
                    </div>
                </div>

                <!-- Company -->
                <div class="mb-4">
                    <label class="form-label">Company:</label>
                    <div class="input-group">
                        <span class="input-group-text" id="inputGroupPrepend">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                                fill="lightslategray">
                                <path
                                    d="M480-80q-82 0-155-31.5t-127.5-86Q143-252 111.5-325T80-480q0-83 31.5-155.5t86-127Q252-817 
                                325-848.5T480-880q83 0 155.5 31.5t127 86q54.5 54.5 86 127T880-480q0 82-31.5 155t-86 127.5q-54.5 54.5-127 
                                86T480-80Zm0-82q26-36 45-75t31-83H404q12 44 31 83t45 75Zm-104-16q-18-33-31.5-68.5T322-320H204q29 50 72.5 
                                87t99.5 55Zm208 0q56-18 99.5-55t72.5-87H638q-9 38-22.5 73.5T584-178ZM170-400h136q-3-20-4.5-39.5T300-480q0-21 
                                1.5-40.5T306-560H170q-5 20-7.5 39.5T160-480q0 21 2.5 40.5T170-400Zm216 0h188q3-20 4.5-39.5T580-480q0-21-1.5-40.5T574-560H386q-3 
                                20-4.5 39.5T380-480q0 21 1.5 40.5T386-400Zm268 0h136q5-20 7.5-39.5T800-480q0-21-2.5-40.5T790-560H654q3 20 
                                4.5 39.5T660-480q0 21-1.5 40.5T654-400Zm-16-240h118q-29-50-72.5-87T584-782q18 33 31.5 68.5T638-640Zm-234 
                                0h152q-12-44-31-83t-45-75q-26 36-45 75t-31 83Zm-200 0h118q9-38 22.5-73.5T376-782q-56 18-99.5 55T204-640Z" />
                            </svg>
                        </span>
                        <input type="text" name="company" class="form-control" placeholder="Company Name"
                            value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                    </div>
                </div>

                <!-- Best time to contact -->
                <div class="mb-4">
                    <label class="form-label">Best time to contact:</label>
                    <div class="time-input-group">
                        <div class="flex-grow-1">
                            <input type="date" name="contact_date" class="form-control"
                                value="<?= htmlspecialchars($_POST['contact_date'] ?? '') ?>">
                        </div>
                        <div style="width: 110px;">
                            <small class="form-text text-muted">Hour Minutes</small>
                            <input type="time" name="contact_time" class="form-control"
                                value="<?= htmlspecialchars($_POST['contact_time'] ?? '') ?>">
                        </div>
                        <div style="width: 80px;">
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
                </div>

                <!-- Inquiry Type -->
                <div class="mb-4">
                    <label class="form-label">Inquiry Type:</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="General Inquiry" id="type1"
                                <?= (isset($_POST['inquiry_type']) && in_array('General Inquiry', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type1">General Inquiry</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]"
                                value="Service Request" id="type2"
                                <?= (isset($_POST['inquiry_type']) && in_array('Service Request', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type2">Service Request</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="Support"
                                id="type3"
                                <?= (isset($_POST['inquiry_type']) && in_array('Support', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type3">Support</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inquiry_type[]" value="others"
                                id="type4"
                                <?= (isset($_POST['inquiry_type']) && in_array('others', $_POST['inquiry_type'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type4">Others</label>
                        </div>
                    </div>
                </div>

                <!-- If Others -->
                <div class="mb-4" id="otherTypeField" style="display: none;">
                    <label class="form-label">If Others:</label>
                    <input type="text" name="other_type" class="form-control" placeholder="Please specify"
                        value="<?= htmlspecialchars($_POST['other_type'] ?? '') ?>">
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="form-label">Description of problem:</label>
                    <textarea name="message" class="form-control" rows="6"
                        placeholder="Please describe your inquiry or problem in detail..."
                        required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn">Send Message</button>
                </div>
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