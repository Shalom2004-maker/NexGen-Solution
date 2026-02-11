$(document).ready(function () {
    function getRules(field) {
        var raw = (field.data("validation") || "").toString().trim();
        return raw ? raw.split(/\s+/) : [];
    }

    function hasRule(rules, rule) {
        return rules.indexOf(rule) !== -1;
    }

    function validateInput(input) {
        var field = $(input);
        var value = (field.val() || "").toString().trim();
        var fieldName = field.attr("name");
        var inputGroup = field.closest(".input-group");

        if (!fieldName) {
            return true;
        }

        var errorField = $("#" + fieldName + "_error");
        var rules = getRules(field);
        var minLength = Number(field.data("min-length") || field.data("min") || 0);
        var maxLength = Number(field.data("max-length") || field.data("max") || 9999);
        var fileSize = Number(field.data("filesize") || 0);
        var fileType = field.data("filetype") || "";
        var errorMessage = "";

        if (rules.length) {
            if (hasRule(rules, "required") && value === "") {
                errorMessage = "This field is required.";
            } else if ((hasRule(rules, "min-length") || hasRule(rules, "min")) && value.length < minLength) {
                errorMessage = "Minimum length is " + minLength + " characters.";
            } else if ((hasRule(rules, "max-length") || hasRule(rules, "max")) && value.length > maxLength) {
                errorMessage = "Maximum length is " + maxLength + " characters.";
            } else if (hasRule(rules, "email") && value !== "") {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    errorMessage = "Please enter a valid email address.";
                }
            } 

            // File validation 
            else if (hasRule(rules, "file") && field[0] && field[0].files && field[0].files.length > 0) {
                var file = field[0].files[0];
                if (fileSize > 0 && file.size > fileSize) {
                    errorMessage = "File size must be less than " + (fileSize / 1024 / 1024) + " MB.";
                } else if (fileType && !file.type.match(fileType)) {
                    errorMessage = "Invalid file type. Allowed types: " + fileType + ".";
                }
            } 
            
            // Numeric validation
            else if (hasRule(rules, "numeric") && value !== "") {
                var numericRegex = /^[0-9]+$/;
                if (!numericRegex.test(value)) {
                    errorMessage = "Please enter a valid numeric value.";
                }
            } 

            // Strong password validation
            else if (hasRule(rules, "strong-password") && value !== "") {
                var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!passwordRegex.test(value)) {
                    errorMessage = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
                }
            } 
            
            // Confirm password validation
            else if (hasRule(rules, "confirm-password") && value !== "") {
                var confirmPasswordField = $("#" + field.data("confirm-password"));
                if (confirmPasswordField.length > 0 && value !== confirmPasswordField.val()) {
                    errorMessage = "Passwords do not match.";
                }
            } 

            // Date validation
            else if (hasRule(rules, "date") && value !== "") {
                var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!dateRegex.test(value)) {
                    errorMessage = "Please enter a valid date in YYYY-MM-DD format.";
                }
            } 
            
            // 
            else if (hasRule(rules, "url") && value !== "") {
                var urlRegex = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w.-]*)*\/?$/;
                if (!urlRegex.test(value)) {
                    errorMessage = "Please enter a valid URL.";
                }
            }
        }

        if (errorMessage) {
            errorField.text(errorMessage).show();
            field.addClass("input-error");
            field.attr("aria-invalid", "true");
            inputGroup.addClass("has-error");
            return false;
        }

        errorField.text("").hide();
        field.removeClass("input-error");
        field.removeAttr("aria-invalid");
        inputGroup.removeClass("has-error");
        return true;
    }

    function runFormValidation(form) {
        var isValid = true;
        var firstInvalid = null;
        form.find("[data-validation]").each(function () {
            if (!validateInput(this)) {
                isValid = false;
                if (!firstInvalid) {
                    firstInvalid = this;
                }
            }
        });
        if (firstInvalid) {
            $(firstInvalid).trigger("focus");
        }
        return isValid;
    }

    $(document).on("input blur change", "[data-validation]", function () {
        validateInput(this);
    });

    $(document).on("submit", "form", function (e) {
        if (!runFormValidation($(this))) {
            e.preventDefault();
        }
    });

    // Keep compatibility with inline onsubmit="return validateForm()".
    window.validateForm = function (formSelector) {
        var form = formSelector ? $(formSelector) : $("#login-form");
        if (!form.length) {
            form = $("form").first();
        }
        return runFormValidation(form);
    };
});
