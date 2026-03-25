(function () {
    "use strict";

    if (window.__nexgenFormValidationInitialized) {
        return;
    }
    window.__nexgenFormValidationInitialized = true;

    function splitRules(raw) {
        if (!raw) {
            return [];
        }
        var value = String(raw).trim();
        return value ? value.split(/\s+/) : [];
    }

    function hasRule(rules, rule) {
        return rules.indexOf(rule) !== -1;
    }

    function addRule(rules, rule) {
        if (!hasRule(rules, rule)) {
            rules.push(rule);
        }
    }

    function parseNumber(value, fallback) {
        var n = Number(value);
        return Number.isFinite(n) ? n : fallback;
    }

    function getData(field, key) {
        if (!field || !field.dataset) {
            return "";
        }
        return field.dataset[key] || "";
    }

    function fieldTagName(field) {
        return field && field.tagName ? field.tagName.toLowerCase() : "";
    }

    function fieldType(field) {
        var type = field && field.getAttribute ? field.getAttribute("type") : "";
        return String(type || "").toLowerCase();
    }

    function isTextLikeField(field) {
        var tag = fieldTagName(field);
        var type = fieldType(field);
        if (tag === "textarea") {
            return true;
        }
        if (tag !== "input") {
            return false;
        }
        return ["text", "password", "email", "url", "search", "tel", "number", "date", "time", "datetime-local", ""].indexOf(type) !== -1;
    }

    function getRules(field) {
        var rules = splitRules(getData(field, "validation"));
        var type = fieldType(field);

        if (field.required) {
            addRule(rules, "required");
        }
        if (field.getAttribute("minlength") !== null || getData(field, "minLength") !== "" || getData(field, "min") !== "") {
            addRule(rules, "min-length");
        }
        if (field.getAttribute("maxlength") !== null || getData(field, "maxLength") !== "" || getData(field, "max") !== "") {
            addRule(rules, "max-length");
        }
        if (field.getAttribute("min") !== null) {
            addRule(rules, "min-value");
        }
        if (field.getAttribute("max") !== null) {
            addRule(rules, "max-value");
        }
        if (type === "email") {
            addRule(rules, "email");
        }
        if (type === "url") {
            addRule(rules, "url");
        }
        if (type === "date") {
            addRule(rules, "date");
        }
        if (type === "file") {
            addRule(rules, "file");
        }

        var name = String(field.getAttribute("name") || "").toLowerCase();
        if (name.indexOf("confirm") !== -1 && (name.indexOf("password") !== -1 || name === "confirm_password")) {
            addRule(rules, "confirm-password");
        }

        return rules;
    }

    function getFieldValue(field) {
        var type = fieldType(field);
        if (type === "checkbox" || type === "radio") {
            return field.checked ? String(field.value || "on") : "";
        }
        return String(field.value || "").trim();
    }

    function getFieldName(field) {
        return String(field.getAttribute("name") || "").trim();
    }

    function sanitizeId(value) {
        return String(value || "").replace(/[^\w\-:.]/g, "_");
    }

    function findErrorFieldByName(fieldName) {
        if (!fieldName) {
            return null;
        }

        var direct = document.getElementById(fieldName + "_error");
        if (direct) {
            return direct;
        }

        var safe = sanitizeId(fieldName) + "_error";
        if (safe !== fieldName + "_error") {
            return document.getElementById(safe);
        }

        return null;
    }

    function createErrorField(field, fieldName) {
        var errorField = document.createElement("div");
        var safeId = sanitizeId(fieldName || field.id || "field") + "_error";
        errorField.id = safeId;
        errorField.className = "validation-error text-danger";
        errorField.style.display = "none";

        var host = field.parentElement;
        if (host && host.classList.contains("input-group")) {
            host.insertAdjacentElement("afterend", errorField);
            return errorField;
        }

        field.insertAdjacentElement("afterend", errorField);
        return errorField;
    }

    function getErrorField(field) {
        var fieldName = getFieldName(field);
        var existing = findErrorFieldByName(fieldName);
        if (existing) {
            return existing;
        }

        if (field.nextElementSibling && field.nextElementSibling.classList.contains("validation-error")) {
            return field.nextElementSibling;
        }

        return createErrorField(field, fieldName);
    }

    function markInvalid(field, message) {
        var errorField = getErrorField(field);
        var inputGroup = field.closest(".input-group");

        errorField.textContent = message;
        errorField.style.display = "block";

        field.classList.add("input-error");
        field.setAttribute("aria-invalid", "true");

        if (inputGroup) {
            inputGroup.classList.add("has-error");
        }

        return false;
    }

    function clearInvalid(field) {
        var errorField = getErrorField(field);
        var inputGroup = field.closest(".input-group");

        errorField.textContent = "";
        errorField.style.display = "none";

        field.classList.remove("input-error");
        field.removeAttribute("aria-invalid");

        if (inputGroup) {
            inputGroup.classList.remove("has-error");
        }

        return true;
    }

    function getMinLength(field) {
        var dataMinLength = getData(field, "minLength");
        var dataMin = getData(field, "min");
        var attrMinLength = field.getAttribute("minlength");
        return parseNumber(dataMinLength || dataMin || attrMinLength, 0);
    }

    function getMaxLength(field) {
        var dataMaxLength = getData(field, "maxLength");
        var dataMax = getData(field, "max");
        var attrMaxLength = field.getAttribute("maxlength");
        return parseNumber(dataMaxLength || dataMax || attrMaxLength, 9999);
    }

    function parseLimit(field, name) {
        var dataValue = getData(field, name);
        var attrValue = field.getAttribute(name);
        var value = dataValue !== "" ? dataValue : attrValue;
        return value === null || value === "" ? null : String(value).trim();
    }

    function getFileSizeLimit(field) {
        return parseNumber(getData(field, "filesize"), 0);
    }

    function parseAcceptTokens(acceptValue) {
        if (!acceptValue) {
            return [];
        }
        return String(acceptValue)
            .split(",")
            .map(function (item) {
                return item.trim().toLowerCase();
            })
            .filter(Boolean);
    }

    function fileMatchesAccept(file, acceptValue) {
        var tokens = parseAcceptTokens(acceptValue);
        if (!tokens.length) {
            return true;
        }

        var fileName = String(file.name || "").toLowerCase();
        var mime = String(file.type || "").toLowerCase();

        return tokens.some(function (token) {
            if (token[0] === ".") {
                return fileName.endsWith(token);
            }
            if (token.endsWith("/*")) {
                return mime.indexOf(token.slice(0, -1)) === 0;
            }
            return mime === token;
        });
    }

    function resolveConfirmPasswordField(field) {
        var selectorOrName = getData(field, "confirmPassword");
        var form = field.form || document;

        if (selectorOrName) {
            if (selectorOrName[0] === "#" || selectorOrName[0] === ".") {
                return document.querySelector(selectorOrName);
            }

            var byId = document.getElementById(selectorOrName);
            if (byId) {
                return byId;
            }

            return form.querySelector("[name=\"" + selectorOrName + "\"]");
        }

        return (
            form.querySelector("[name=\"new_password\"]") ||
            form.querySelector("[name=\"password\"]") ||
            form.querySelector("[name=\"pass\"]")
        );
    }

    function compareFieldValue(rawValue, field) {
        var type = fieldType(field);
        if (type === "number" || type === "range") {
            var a = Number(rawValue);
            var b = Number(field.value);
            if (!Number.isFinite(a) || !Number.isFinite(b)) {
                return null;
            }
            return a - b;
        }

        if (type === "date" || type === "time" || type === "datetime-local") {
            if (!rawValue || !field.value) {
                return null;
            }
            if (rawValue === field.value) {
                return 0;
            }
            return rawValue < field.value ? -1 : 1;
        }

        return null;
    }

    function isValidatableField(field) {
        if (!field || !field.tagName || field.disabled) {
            return false;
        }

        var tag = fieldTagName(field);
        if (tag !== "input" && tag !== "select" && tag !== "textarea") {
            return false;
        }

        var type = fieldType(field);
        if (type === "hidden" || type === "submit" || type === "button" || type === "reset") {
            return false;
        }

        return getRules(field).length > 0;
    }

    function validateInput(field) {
        if (!isValidatableField(field)) {
            return true;
        }

        var rules = getRules(field);
        var value = getFieldValue(field);
        var type = fieldType(field);

        var minLength = getMinLength(field);
        var maxLength = getMaxLength(field);
        var minValue = parseLimit(field, "min");
        var maxValue = parseLimit(field, "max");
        var fileSizeLimit = getFileSizeLimit(field);
        var customFileType = getData(field, "filetype");
        var acceptType = field.getAttribute("accept") || "";

        var errorMessage = "";

        if (hasRule(rules, "required")) {
            if ((type === "checkbox" || type === "radio") && field.name) {
                var group = (field.form || document).querySelectorAll("[name=\"" + field.name + "\"]");
                var checked = Array.prototype.some.call(group, function (item) {
                    return item.checked;
                });
                if (!checked) {
                    errorMessage = "This field is required.";
                }
            } else if (type === "file") {
                if (!field.files || field.files.length === 0) {
                    errorMessage = "This field is required.";
                }
            } else if (value === "") {
                errorMessage = "This field is required.";
            }
        }

        if (!errorMessage && (hasRule(rules, "min-length") || hasRule(rules, "min")) && value !== "" && isTextLikeField(field)) {
            if (value.length < minLength) {
                errorMessage = "Minimum length is " + minLength + " characters.";
            }
        }

        if (!errorMessage && (hasRule(rules, "max-length") || hasRule(rules, "max")) && value !== "" && isTextLikeField(field)) {
            if (value.length > maxLength) {
                errorMessage = "Maximum length is " + maxLength + " characters.";
            }
        }

        if (!errorMessage && hasRule(rules, "email") && value !== "") {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = "Please enter a valid email address.";
            }
        }

        if (!errorMessage && hasRule(rules, "url") && value !== "") {
            var urlRegex = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,})([/\w.-]*)*\/?$/i;
            if (!urlRegex.test(value)) {
                errorMessage = "Please enter a valid URL.";
            }
        }

        if (!errorMessage && hasRule(rules, "date") && value !== "") {
            var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(value)) {
                errorMessage = "Please enter a valid date in YYYY-MM-DD format.";
            }
        }

        if (!errorMessage && (type === "number" || type === "range") && value !== "" && !Number.isFinite(Number(value))) {
            errorMessage = "Please enter a valid numeric value.";
        }

        if (!errorMessage && hasRule(rules, "min-value") && value !== "" && minValue !== null) {
            if (type === "number" || type === "range") {
                var minNumber = Number(minValue);
                if (Number.isFinite(minNumber) && Number(value) < minNumber) {
                    errorMessage = "Minimum value is " + minValue + ".";
                }
            } else {
                var minCompareField = document.createElement("input");
                minCompareField.type = type || "text";
                minCompareField.value = String(minValue);
                var minCompareResult = compareFieldValue(value, minCompareField);
                if (minCompareResult !== null && minCompareResult < 0) {
                    errorMessage = "Value must be on or after " + minValue + ".";
                }
            }
        }

        if (!errorMessage && hasRule(rules, "max-value") && value !== "" && maxValue !== null) {
            if (type === "number" || type === "range") {
                var maxNumber = Number(maxValue);
                if (Number.isFinite(maxNumber) && Number(value) > maxNumber) {
                    errorMessage = "Maximum value is " + maxValue + ".";
                }
            } else {
                var maxCompareField = document.createElement("input");
                maxCompareField.type = type || "text";
                maxCompareField.value = String(maxValue);
                var maxCompareResult = compareFieldValue(value, maxCompareField);
                if (maxCompareResult !== null && maxCompareResult > 0) {
                    errorMessage = "Value must be on or before " + maxValue + ".";
                }
            }
        }

        if (!errorMessage && hasRule(rules, "numeric") && value !== "") {
            var numericRegex = /^[0-9]+$/;
            if (!numericRegex.test(value)) {
                errorMessage = "Please enter a valid numeric value.";
            }
        }

        if (!errorMessage && hasRule(rules, "strong-password") && value !== "") {
            var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(value)) {
                errorMessage = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
            }
        }

        if (!errorMessage && hasRule(rules, "confirm-password") && value !== "") {
            var confirmPasswordField = resolveConfirmPasswordField(field);
            if (confirmPasswordField && value !== String(confirmPasswordField.value || "")) {
                errorMessage = "Passwords do not match.";
            }
        }

        if (!errorMessage && hasRule(rules, "file") && type === "file" && field.files && field.files.length > 0) {
            var file = field.files[0];

            if (fileSizeLimit > 0 && file.size > fileSizeLimit) {
                errorMessage = "File size must be less than " + (fileSizeLimit / 1024 / 1024).toFixed(2) + " MB.";
            } else if (customFileType) {
                var customPattern = new RegExp(customFileType, "i");
                if (!customPattern.test(file.type) && !customPattern.test(file.name)) {
                    errorMessage = "Invalid file type.";
                }
            } else if (!fileMatchesAccept(file, acceptType)) {
                errorMessage = "Invalid file type. Allowed types: " + acceptType + ".";
            }
        }

        if (errorMessage) {
            return markInvalid(field, errorMessage);
        }

        return clearInvalid(field);
    }

    function getValidatableFields(form) {
        if (!form || !form.querySelectorAll) {
            return [];
        }

        return Array.prototype.filter.call(
            form.querySelectorAll("input, select, textarea"),
            isValidatableField
        );
    }

    function runFormValidation(form) {
        var isValid = true;
        var firstInvalid = null;

        getValidatableFields(form).forEach(function (field) {
            if (!validateInput(field)) {
                isValid = false;
                if (!firstInvalid) {
                    firstInvalid = field;
                }
            }
        });

        if (firstInvalid && typeof firstInvalid.focus === "function") {
            firstInvalid.focus();
        }

        return isValid;
    }

    function handleFieldValidationEvent(event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var field = target.closest("input, select, textarea");
        if (!field) {
            return;
        }

        if (isValidatableField(field)) {
            validateInput(field);
        }
    }

    function markFormsNoValidate() {
        Array.prototype.forEach.call(document.querySelectorAll("form"), function (form) {
            if (getValidatableFields(form).length > 0) {
                form.setAttribute("novalidate", "novalidate");
            }
        });
    }

    document.addEventListener("input", handleFieldValidationEvent, true);
    document.addEventListener("blur", handleFieldValidationEvent, true);
    document.addEventListener("change", handleFieldValidationEvent, true);

    document.addEventListener(
        "submit",
        function (event) {
            var form = event.target;
            if (!form || !form.tagName || form.tagName.toLowerCase() !== "form") {
                return;
            }

            if (!runFormValidation(form)) {
                event.preventDefault();
            }
        },
        true
    );

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", markFormsNoValidate, { once: true });
    } else {
        markFormsNoValidate();
    }

    window.validateForm = function (formSelector) {
        var form = null;

        if (typeof formSelector === "string" && formSelector.trim() !== "") {
            form = document.querySelector(formSelector);
        } else if (formSelector && formSelector.tagName && formSelector.tagName.toLowerCase() === "form") {
            form = formSelector;
        }

        if (!form) {
            form = document.getElementById("login-form") || document.querySelector("form");
        }

        if (!form) {
            return true;
        }

        return runFormValidation(form);
    };
})();
