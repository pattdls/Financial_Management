document.addEventListener("DOMContentLoaded", function () {
    // This will be triggered when the page is fully loaded
    const clientDropdown = document.getElementById("client-dropdown");
    const projectDropdown = document.getElementById("project-dropdown");

    // Function to update projects based on selected client
    function updateProjects(clientID) {
        if (clientID === "") {
            projectDropdown.innerHTML = '<option value="">Select Project</option>';
            projectDropdown.disabled = true;
            return;
        }

        console.log("Fetching projects for client ID:", clientID);  // Debugging line

        fetch(`expense_logic.php?client_id=${clientID}`)
            .then(response => response.json())
            .then(projects => {
                console.log("Projects received:", projects);  // Debugging line
                
                // Filter ongoing main projects
                const ongoingMainProjects = projects.filter(p => p.type === "main" && p.project_status.toLowerCase() === "ongoing");

                if (ongoingMainProjects.length === 0) {
                    projectDropdown.innerHTML = '<option value="">No projects found</option>';
                    projectDropdown.disabled = true;
                    return;
                }

                // Include all add-ons whose main project is ongoing
                const filteredProjects = projects.filter(p => {
                    if (p.type === "main") return p.project_status.toLowerCase() === "ongoing";
                    if (p.type === "addon") return ongoingMainProjects.some(main => main.project_id === p.main_project_id);
                    return false;
                });

                let options = `<option value="">Select Project</option>`;
                filteredProjects.forEach(project => {
                     let option = "";

                    if (project.type === "main") {
                        option = `
                        <option value="project-${project.project_id}" 
                            data-type="main"
                            data-projectid="${project.project_id}"
                            data-budget="${project.allocated_budget}"
                            data-expenses="${project.actual_expenses}"
                            data-over="${project.over_spending ? 1 : 0}">
                            ${project.project_name}
                        </option>`;
                    } 
                    
                    if (project.type === "addon") {
                        option = `
                        <option value="addon-${project.addon_id}" 
                            data-type="addon"
                            data-projectid="${project.addon_id}"
                            data-mainid="${project.main_project_id}"
                            data-mainname="${project.main_project_name}"
                            data-budget="${project.allocated_budget}"
                            data-expenses="${project.actual_expenses}"
                            data-over="${project.over_spending ? 1 : 0}">
                            ${project.addon_name}
                        </option>`;
                    }

                    options += option;
                });

                console.log("Main project id:", $('#main_project_id').val());
                console.log("Addon id:", $('#addon_id').val());

                projectDropdown.innerHTML = options;
                projectDropdown.disabled = false;

                // This is for initializing dropdown-option format
                 $('#project-dropdown').select2({
                    placeholder: "Select Project",
                    templateResult: formatProjectAddon,
                    templateSelection: formatProjectAddon,
                    minimumResultsForSearch: Infinity, //this is to disable the search within the dropdown
                    dropdownCssClass: 'bootstrap-select', 
                    selectionCssClass: 'form-select bic',
                    dropdownAutoWidth: true,
                    width: '100%' // reuse original CSS class
                   
                });
                // Manually trigger placeholder display
                $('#project-dropdown').val('').trigger('change');

                $('#project-dropdown').on('change', function () {
                    const selected = $(this).find(':selected');
                    const type = selected.data('type');

                    const budget = parseFloat(selected.data('budget')) || 0;
                    const expenses = parseFloat(selected.data('expenses')) || 0;
                    const isOver = selected.data('over') == 1;
                    const name = selected.text();

                      console.log("=== DEBUGGING SELECTED OPTION ===");
                        console.log("Selected option raw HTML:", selected[0].outerHTML);
                        console.log("Data-projectid:", selected.data('projectid'));
                        console.log("Data-project_id:", selected.data('project_id'));
                        console.log("Data-mainid:", selected.data('mainid'));
                        console.log("=================================");

                    if (type === "main") {
                        $('#main_project_id').val(selected.data('projectid'));
                        $('#addon_id').val(""); // clear addon
                    } else if (type === "addon") {
                        $('#main_project_id').val(selected.data('mainid'));
                        $('#addon_id').val(selected.val().replace("addon-", "")); 
                    } else {
                        $('#main_project_id').val("");
                        $('#addon_id').val("");
                    }
                    if(isOver){
                        const modalBody = document.querySelector("#limitWarningModal .modal-body");
                        modalBody.textContent = `Your  ${name} ${type === 'addon' ? 'add-on project' : 'project'} has reached its total allocated budget of ${formatCurrency(budget)}. Your current expenses for this project is now ${formatCurrency(expenses)}`;
                        new bootstrap.Modal(document.getElementById("limitWarningModal")).show();
                    }
                });
            })
            .catch(error => {
                console.error("Error fetching projects:", error);
            });
    }
    function formatProjectAddon(state){ 
        if (!state.id) return state.text;

        const $option = $(state.element);
        const type = $option.data('type');
        if (type === "addon") {
            return $(`
                    <div>
                        <div><strong>${state.text}</strong></div>
                        <small class="text-muted">Addon For: ${$option.data('mainname')}</small>
                    </div>
                    `);
        }

    return $(`<div>${state.text}</div>`);

    }
    function formatCurrency(amount) {
    return amount.toLocaleString('en-PH', { 
        style: 'currency', 
        currency: 'PHP' 
    });
}

    // Trigger project update when client is changed
    clientDropdown.addEventListener("change", function () {
        updateProjects(this.value);
    });

    // Also trigger the project update when the page loads (in case a client is already pre-selected)
    const selectedClientID = clientDropdown.value;
    if (selectedClientID) {
        updateProjects(selectedClientID);
    }
});


//FORM VALIDATIONS START HERE
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[name="expenseForm"]');
    const previewBeforeSaveBtn = document.getElementById('previewBeforeSave');
    const finalSubmit = document.getElementById('finalSubmit');
    let tempFormData = null;

    previewBeforeSaveBtn.addEventListener('click', function () {
        showLoading();

        let hasFrontendError = false;
        const rows = form.querySelectorAll('#batchRows tr');

        // Clear old errors
        rows.forEach(row => {
            const errorBoxes = row.querySelectorAll('.error-message-batch');
            errorBoxes.forEach(box => box.innerText = '');
        });

        // FRONTEND VALIDATION
        rows.forEach((row) => {
            const description = row.querySelector('input[name="item_name[]"]').value.trim();
            const storeName = row.querySelector('input[name="store_name[]"]').value.trim();
            const amount = row.querySelector('input[name="amount[]"]').value.trim();
            const invoiceNum = row.querySelector('input[name="invoice_num[]"]').value.trim();

            const errorBox1 = row.querySelector('.errorBoxdes');
            const errorBox2 = row.querySelector('.errorBoxstore');
            const errorBox3 = row.querySelector('.errorBoxamount');
            const errorBox4 = row.querySelector('.errorBoxinvoice');

            const wordCount = str => str.replace(/[^\w\s]|_/g, "").split(/\s+/).filter(w => w).length;

             if (isGibberish(description)) {
                errorBox1.innerText = "Please enter a valid item name.";
                hasFrontendError = true;
            }
            else if (wordCount(description) < 1 || wordCount(description) > 10) {
                errorBox1.innerText = "Expense Description must contain 1–10 words.";
                hasFrontendError = true;
            }
            else {
                errorBox1.innerText = "";
            }

            
            if (isGibberish(storeName)) {
                errorBox2.innerText = "Please enter a valid store name.";
                hasFrontendError = true;
            }
            else if (wordCount(storeName) < 1 || wordCount(storeName) > 10) {
                errorBox2.innerText = "Store Name must contain 1–10 words.";
                hasFrontendError = true;
            }
            else {
                errorBox2.innerText = "";
            }

            const filteredAmount = amount.replace(/,/g, '');
            const hasSpace = /\s/.test(filteredAmount);
            const isValidNumber = /^(?!-)[0-9]+(\.[0-9]+)?$/.test(filteredAmount);

            if (!isValidNumber || hasSpace || Number(filteredAmount) < 5) {
                errorBox3.innerText = `Amount must be a valid number, not less than 5 pesos, no spaces, and does not start with a zero.`;
                hasFrontendError = true;
            }

            if (invoiceNum === "") {
                errorBox4.innerText = `Invoice Number is required.`;
                hasFrontendError = true;
            }

        });

        // Send backend validation request even if frontend has errors
        tempFormData = new FormData(form);
        tempFormData.append('save_batch_expense', '1');
        tempFormData.append('validate_only', '1'); // This is to signal the backend to not insert the data yet

        fetch('expense_logic.php', {
            method: 'POST',
            body: tempFormData
        })
        .then(response => {
            if (!response.ok) {
        return response.text().then(text => {
            throw new Error(`HTTP ${response.status} - ${text}`);
        });
    }

    const ct = response.headers.get("content-type") || "";
    if (!ct.includes("application/json")) {
        return response.text().then(text => {
            throw new Error("Server did not return JSON. Response: " + text);
        });
    }

    return response.json();
})
        .then(data => {
            hideLoading();

            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            let hasBackendError = false;

            if (data.gemini_debug) {
                console.log("Gemini Reply:", data.gemini_debug);
            }

            if (data.status === 'error') {
                console.log("Main project id:", $('#main_project_id').val());
                console.log("Addon id:", $('#addon_id').val());
                hasBackendError = true;
                data.errors.forEach(err => {
                    const rows = form.querySelectorAll('#batchRows tr');
                    const row = rows[err.row];
                     if (!row) {
                        // Handle global errors (like Gemini mismatch) here
                        if (err.type === 'receipt_content_mismatch') {
                            showAlertMessage(err.message, 'danger');
                        }
                        return;
                    }

                    if (err.type === 'invoice_duplicate') {
                        row.querySelector('.errorBoxinvoice').innerHTML = err.message;
                    } else if (err.type === 'item_name_count') {
                        row.querySelector('.errorBoxdes').innerHTML = err.message;
                    } else if (err.type === 'store_name_count') {
                        row.querySelector('.errorBoxstore').innerHTML = err.message;
                    } else if (err.type === 'amount_format') {
                        row.querySelector('.errorBoxamount').innerHTML = err.message;
                    } else if (['file_duplicate', 'file_invalid', 'file_too_large'].includes(err.type)) {
                        row.querySelector('.errorBoxfile').innerHTML = err.message;
                    } 
                });
            }

            // Show modal only if no frontendand backend errors
            if (!hasFrontendError && !hasBackendError && data.status === 'success') {
                generateReviewTable();
                const modal = new bootstrap.Modal(document.getElementById('confirmSubmitForm'));
                modal.show();
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showAlertMessage('An error occurred. Please try again.', 'danger');
        });
    });

    // FINAL SUBMIT
    finalSubmit.addEventListener('click', function () {
        if (!tempFormData) return;
        showSavingLoading();
        tempFormData.set('validate_only', '0'); // This tells backend to insert into DB

        fetch('expense_logic.php', {
            method: 'POST',
            body: tempFormData
        })
        .then(response => {
             if (!response.ok) {
        return response.text().then(text => {
            throw new Error(`HTTP ${response.status} - ${text}`);
        });
    }
    const ct = response.headers.get("content-type") || "";
    if (!ct.includes("application/json")) {
        return response.text().then(text => {
            throw new Error("Server did not return JSON. Response: " + text);
        });
    }
    return response.json();
})
        .then(data => {
            hideSavingLoading();
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('confirmSubmitForm'));
            modalInstance.hide();
            console.log("Main project id:", $('#main_project_id').val());
            console.log("Addon id:", $('#addon_id').val());

            if (data.status === 'success') {
                showAlertMessage(data.message, 'success', true);
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlertMessage(data.message, 'danger');
            }
        })
        .catch(error => {
            hideSavingLoading();
            console.error('Error:', error);
            showAlertMessage('An error occurred while saving.', 'danger');
        });
    });
});


// Newly added for preview table modal and confirm submission
function generateReviewTable() {
    const rows = document.querySelectorAll('#batchRows tr');
    const clientName = document.querySelector('#client-dropdown option:checked')?.textContent.trim() || 'N/A';
    const projectName = document.querySelector('#project-dropdown option:checked')?.textContent.trim() || 'N/A';
    const expenseDate = document.querySelector('input[name="date"]')?.value || 'N/A';

    let html = `
    <div class="mb-4" style="line-height: 1.8;">
            <strong>Date:</strong> ${expenseDate}<br>
            <strong>Client:</strong> ${clientName}<br>
            <strong>Project:</strong> ${projectName}<br> 
        </div>
        <table class="table table-bordered border-secondary px-0 table-sm" style="border-radius: 0;">
            <thead class="table-dark text-center">
                <tr>
                    <th>Item</th>
                    <th>Store</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Invoice #</th>
                    <th>Receipt File</th>
                </tr>
            </thead>
        <tbody>
    `;
    // To fetch the actual input of users for each field
     rows.forEach(row => {
            const item = row.querySelector('input[name="item_name[]"]').value.trim();
            const store = row.querySelector('input[name="store_name[]"]').value.trim();
            const categorySelect = row.querySelector('select[name="category_num[]"]');
            const categoryNum = categorySelect.value.trim(); // e.g. 1001
            const categoryTitle = categorySelect.options[categorySelect.selectedIndex].text;
            const amount = row.querySelector('input[name="amount[]"]').value.trim();
            const payment = row.querySelector('select[name="payment_method[]"]').value.trim();
            const otherPay = row.querySelector('input[name="other_payment_method[]"]');
            const paymentDisplay = payment === 'Others' ? `Others (${otherPay.value.trim()})` : payment;
            const invoice = row.querySelector('input[name="invoice_num[]"]').value.trim();
            // This is for user to click and open the image in new tab
            const fileInput = row.querySelector('input[name="receipt_file[]"]');
            const file = fileInput.files[0];
            let filePreview = 'No file';

            if (file) {
                const url = URL.createObjectURL(file);
                filePreview = `
                    <a href="${url}" target="_blank" title="Click to view full image">
                        <img src="${url}" alt="Receipt Preview" class="img-fluid rounded shadow-sm" style="max-width: 100px; max-height: 100px;" loading="lazy">
                    </a>
                `;    }

    html += `
        <tr>
            <td class="text-center">${item}</td>
            <td class="text-center">${store}</td>
            <td class="text-center">${categoryTitle} (${categoryNum})</td>
            <td class="text-center">₱${amount}</td>
            <td class="text-center">${paymentDisplay}</td>
            <td class="text-center">${invoice}</td>
            <td class="text-center">${filePreview}</td>
        </tr>
    `;
        });

        html += `</tbody></table>`;
        document.getElementById('reviewContent').innerHTML = html;
    }



    // Function to display the alert message above the form
function showAlertMessage(message, alertType) {
   //This code will remove the previous div elements 
    document.querySelectorAll('.alert').forEach(alert => alert.remove());
     // Create the alert div
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${alertType} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <div style="flex: 1 1 auto; word-break: break-word; min-width: 0;">&nbsp;&nbsp;&nbsp;&nbsp;
            ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;

    // To specify the position or location of alert message
    const alertPlaceholder = document.getElementById("alertPlaceholder");
    if (alertPlaceholder) {
        alertPlaceholder.appendChild(alertDiv);
    }
    
}


// Script for adding rows
function addRow(){
    const tableBody = document.getElementById("batchRows");
    const rowCount = tableBody.querySelectorAll("tr").length;

    if (rowCount >= 10) {
        // Show modal instead of adding a new row
        const rowLimitModal = new bootstrap.Modal(document.getElementById('rowLimit'));
        rowLimitModal.show();
        return;
    }

    const row = document.querySelector("#batchRows tr").cloneNode(true);
    const inputs = row.querySelectorAll('input, select, textarea');
    const errorContainers = row.querySelectorAll('.error-message-batch');

    inputs.forEach(input => {
        if (input.type === 'text' || input.type === 'file') {
            input.value = ''; // Clear text and file inputs
        } else if (input.type === 'select-one') {
            input.selectedIndex = 0; // Reset select to the first option
        }
    });

    errorContainers.forEach(container =>{
        container.textContent = '';
    })
    document.getElementById("batchRows").appendChild(row);
}

//To clear input fields
function clearRow(button){
    const row = button.closest('tr');
    const inputs = row.querySelectorAll('input');
    const selects = row.querySelectorAll('select');
    const errorBoxes = row.querySelectorAll('.error-message, .error-message1');

   // Clear input fields
   for (let input of inputs) {
    if (input.type === 'file'){
        input.value = '';
    } else {
        input.value = '';
    }
   }

   for (let select of selects) {
    select.selectedIndex = 0;
   }
   for (let errorBox of errorBoxes) {
    errorBox.textContent = '';
   }
    // Clear Select2 specifically
    const projectDropdown = $('#project-dropdown');
    if (projectDropdown.length) {
        projectDropdown.val(null).trigger('change'); 
    }

   // Clear file upload error message outside the row
    const globalFileError = document.getElementById('errorBoxfile');
    if (globalFileError) globalFileError.textContent = '';

      //  Hide the 'other payment method' input if shown
    const otherPayment = formWrapper.querySelector('#other-payment-input');
    if (otherPayment) {
        otherPayment.style.display = 'none';
        otherPayment.value = '';
    }
}

//To remove the entire row
function removeRow(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('batchRows');
    if (tbody.rows.length > 1){
        row.remove();
    }
}

// Script to handle "OTHERS" from PAYMENT METHOD OPTIONS
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.payment-method-dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('change', function() {
            const otherInput = this.parentElement.querySelector('.other-payment-input');
            if (this.value === 'Others') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        });
    });

    document.querySelector('form').addEventListener('submit', function() {
        document.querySelectorAll('.payment-method-dropdown').forEach(function(dropdown) {
            const otherInput = dropdown.parentElement.querySelector('.other-payment-input');
            if (dropdown.value === 'Others' && otherInput.value.trim() !== '') {
                //Overwrite the selected option value inside the <select>
                const selectedOption = dropdown.querySelector('option[value="Others"]');
                if (selectedOption) {
                    selectedOption.value = otherInput.value.trim();       // Change the value
                }
                dropdown.value = otherInput.value.trim();  // Force the select to have the new value
            }
        });
    });
});

// Function for loading state when validating inputs
 function showLoading() {
    const loadingWrap = document.getElementById('loadingStateWrap');
    const loadingState = document.getElementById('loadingStateBatch');
    const form = document.getElementById('expense-form');
    loadingWrap.style.display = 'block';
    loadingState.style.display = 'flex';
    form.classList.add('disabled');
}

function hideLoading() {
    const loadingWrap = document.getElementById('loadingStateWrap');
    const loadingState = document.getElementById('loadingStateBatch');
    const form = document.getElementById('expense-form');
     loadingWrap.style.display = 'none';
    loadingState.style.display = 'none';
    form.classList.remove('disabled');
}

// Function for loading state when saving entries
function showSavingLoading() {
    const loadingWrap2 = document.getElementById('loadingStateWrap2');
    const loadingState2 = document.getElementById('loadingStateBatch2');
    loadingWrap2.style.display = 'block';
    loadingState2.style.display = 'flex';
}

function hideSavingLoading() {
    const loadingWrap2 = document.getElementById('loadingStateWrap2');
    const loadingState2 = document.getElementById('loadingStateBatch2');
    loadingWrap2.style.display = 'none';
    loadingState2.style.display = 'none';
}
function isGibberish(text) {
    text = text.trim();

    // Too short to be meaningful
    if (text.length < 3) return true;

    // Mostly numbers or symbols
    if (/^[0-9!@#$%^&*()_+=\-\[\]{};:'",.<>/?\\|]+$/.test(text)) return true;

    // Contains no vowels → very likely gibberish
    if (!/[aeiouAEIOU]/.test(text)) return true;

    // Repeated characters like "aaa", "asdasd", "xxxxxx"
    if (/(.)\1{2,}/.test(text)) return true;

    // Too many consonants in a row (e.g., "njksdghsdg")
    if (/[bcdfghjklmnpqrstvwxyz]{5,}/i.test(text)) return true;

    // Mixed letters + numbers with no spaces (e.g., "as3d4f5g")
    if (/^[A-Za-z0-9]+$/.test(text) && /[A-Za-z]+/.test(text) && /\d+/.test(text)) {
        return true;
    }

    return false;
}


