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
                
                // To filter only ongoing projects for recording expense
                const ongoingProjects = projects.filter(project => project.project_status === "Ongoing");
                if (ongoingProjects.length === 0) {
                    projectDropdown.innerHTML = '<option value="">No projects found</option>';
                    projectDropdown.disabled = true;
                    return;
                }

                let options = `<option value="">Select Project</option>`;
                ongoingProjects.forEach(project => {
                    if(project.type === "main"){
                        options += `
                        <option value="project-${project.project_id}" 
                        data-type="main" 
                        data-projectid="${project.project_id}"
                        data-budget = "${project.allocated_budget}"
                        data-expenses="${project.actual_expenses}" 
                        data-over="${project.over_spending ? 1 : 0}">
                        ${project.project_name}</option>`;
                    } else if (project.type === "addon"){
                        options += `
                        <option value="addon-${project.addon_id}" 
                        data-type="addon" 
                        data-mainid="${project.main_project_id}" 
                        data-mainname="${project.main_project_name}"
                        data-budget="${project.allocated_budget}" 
                        data-expenses="${project.actual_expenses}" 
                        data-over="${project.over_spending ? 1 : 0}">
                        ${project.addon_name}</option>`;
                    }
                });

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
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[name="expenseForm"]');
    let tempFormData = null;
   
    forms.forEach(form => {
        const previewBtn = document.getElementById("previewBeforeSave");
        const finalSubmitBtn = document.getElementById("finalSubmit");

    previewBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default form submission
        

        const errorBox1 = document.getElementById("errorBoxdes");
        const errorBox2 = document.getElementById("errorBoxstore");
        const errorBox3 = document.getElementById("errorBoxamount");
        const errorBox4 = document.getElementById("errorBoxinvoice");
        const errorBox5 = document.getElementById("errorBoxfile");

        errorBox1.innerHTML = "";
        errorBox2.innerHTML = "";
        errorBox3.innerHTML = "";
        errorBox4.innerHTML = "";
        errorBox5.innerHTML = "";

        // Validate Description and Store fields
        const description = document.getElementById("description").value.trim();
        const storeName = document.getElementById("store").value.trim();
        const amount = document.getElementById("amount").value.trim();

        //To store errors for showing all together with backend 
        let frontendErrors = {
            description: "",
            storeName: "",
            amount: ""
        };

        const wordCount = str => str.replace(/[^\w\s]|_/g, "").split(/\s+/).filter(w => w).length;
        
        //To limit input to 10 words only
        if (isGibberish(description)){
             frontendErrors.description = "Please input valid description.";
        }
        else if (wordCount(description) < 1 || wordCount(description) > 10) {
            frontendErrors.description = "Expense Description must contain at least one word but not greater than ten words.";
            
        }
        
         if (isGibberish(storeName)){
             frontendErrors.storeName = "Please input valid store name.";
        }
        else if (wordCount(storeName) < 1 || wordCount(storeName) > 10) {
            frontendErrors.storeName = "Store Name must contain at least one word but not greater than ten words.";
            
        }

        const filteredAmount = amount.replace(/,/g, ''); //Removes the comma
        const hasSpace = /\s/.test(filteredAmount); 
        
        const isValidNumber = /^(?!-)[0-9]+(\.[0-9]+)?$/.test(filteredAmount);

        //Condition to check if amount is valid
        if (!isValidNumber || hasSpace || Number(filteredAmount) < 5)  {
           frontendErrors.amount = "Amount must be a valid number, greater than 5 pesos, and must not include spaces.";
            
        }


        // Send backend validation request even if frontend has errors
        tempFormData = new FormData(form);
        tempFormData.append('save_expense', '1');
        tempFormData.append('validate_only_scan', '1'); // This is to signal the backend to not insert the data yet
        tempFormData.append('frontend_errors', JSON.stringify(frontendErrors));
        //Condition to flag for error/signal to backend
        tempFormData.append('frontend_error', (frontendErrors.description || frontendErrors.storeName || frontendErrors.amount) ? '1' : '0');

        showSavingLoading();
        fetch('expense_logic.php', {
            method: 'POST',
            body: tempFormData
        })
        .then(response => response.json()) 
        .then(data => {
            
            hideSavingLoading();
             // Show frontend errors
                if (data.frontend_errors) {
                    errorBox1.innerHTML = data.frontend_errors.description || "";
                    errorBox2.innerHTML = data.frontend_errors.store || "";
                    errorBox3.innerHTML = data.frontend_errors.amount || "";
                }

                // Show backend errors
                if (data.invoice_error) {
                    errorBox4.innerHTML = data.invoice_error;
                }

                if (data.file_error) {
                    errorBox5.innerHTML = data.file_error;
                }

                if (data.general_error) {
                    showAlertMessage(data.general_error, 'danger');
                }

                // ONLY show modal if all validations passed
                const hasFrontendError = data.frontend_errors &&
                    (data.frontend_errors.description || data.frontend_errors.store || data.frontend_errors.amount);

                const hasBackendError = data.invoice_error || data.file_error || data.general_error;

                if (!hasFrontendError && !hasBackendError && data.status === 'success') {
                    const modal = new bootstrap.Modal(document.getElementById('confirmSubmitForm'));
                    modal.show();
                }
            })
        .catch(error => {
            hideSavingLoading();
            console.error('Error:', error);
            showAlertMessage('An error occurred. Please try again.', 'danger');
        });
    });
        finalSubmitBtn.addEventListener('click', function () {
            if (!tempFormData) return;

            tempFormData.set('validate_only_scan', '0'); // This tells backend to insert into DB
            showSavingLoading();
            fetch('expense_logic.php', {
                method: 'POST',
                body: tempFormData
            })
                .then(response => response.json())
                .then(data => {
                    hideSavingLoading();
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('confirmSubmitForm'));
                    modalInstance.hide();

                    if (data.status === 'success') {
                        showAlertMessage(data.message, 'success', true);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlertMessage(data.message || 'Failed to save. Please try again.', 'danger');
                    }
                })
                .catch(error => {
                    hideSavingLoading();
                    console.error('Error:', error);
                    showAlertMessage('An error occurred while saving.', 'danger');
                });
        });
    });
});

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


//To display the scanned data from the receipt
function previewImage(input){
    const file = input.files[0];
    if(file){      
        startScanning();

        const reader = new FileReader();
        reader.onload = function(e) {
        document.getElementById('preview').src = e.target.result;    
        // document.getElementById('dragDropContainer').style.display = 'none';
        // document.getElementById('dragDropContainer').style.visibility = 'hidden'
        // document.getElementById('previewContainer').style.display = 'block';
        // document.getElementById('previewContainer').style.visibility = 'visible';
        // document.getElementById('uploadAnother').style.display = 'inline-block';
        // document.getElementById('uploadAnother').style.visibility = 'visible';
        // document.getElementById('errorBoxfile').textContent = '';
        }
        reader.readAsDataURL(file);

        const formData = new FormData();
        formData.append('receipt_file', file);
fetch('../forms_logic/process_receipt.php', {
    method: 'POST',
    body: formData
})
.then(res => res.text())
.then(text => {
    console.log('Raw response:', text);
    console.log("OCR Text:", text);// Log raw response
    try {
        const data = JSON.parse(text);
        if (data.error) {
            console.error('Server error:', data.error, data.details);
            const detailText = data.details ? ' - ' + (typeof data.details === 'string' ? data.details : JSON.stringify(data.details)) : '';
            showAlertMessage('Error processing receipt: ' + data.error + detailText, 'danger');
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('dragDropContainer').style.display = 'flex';
            document.getElementById('dragDropContainer').style.visibility = 'visible';
            return;
        }
        const values = JSON.parse(data.structured || '{}');
        document.querySelector('[name="store_name"]').value = values.store || '';
        document.querySelector('[name="item_name"]').value = values.items?.[0]?.item_name || '';
        document.querySelector('[name="amount"]').value = values.total_amount || '';
        document.querySelector('[name="invoice_num"]').value = values.invoice_number || '';
        document.querySelector('[name="payment_method"]').value = values.payment_method || '';
        document.querySelector('[name="date"]').value = values.date || '';


    } catch (e) {
        console.error('JSON parse error:', e, 'Raw response:', text);
        showAlertMessage('Image does not appear to be a receipt. Please try again with a proper, valid receipt photo.', 'danger');
        
    }
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('dragDropContainer').style.display = 'none';
        document.getElementById('dragDropContainer').style.visibility = 'hidden'
        document.getElementById('previewContainer').style.display = 'block';
        document.getElementById('previewContainer').style.visibility = 'visible';
        document.getElementById('uploadAnother').style.display = 'inline-block';
        document.getElementById('uploadAnother').style.visibility = 'visible';
        document.getElementById('errorBoxfile').textContent = '';
})
.catch(error => {
    console.error('Error:', error);
    showAlertMessage('Failed to process receipt. Please try again.', 'danger');

     document.getElementById('loadingState').style.display = 'none';
     document.getElementById('dragDropContainer').style.display = 'flex';
     document.getElementById('dragDropContainer').style.visibility = 'visible';
});
   

}
}

// Function for the loading state:
 function startScanning() {
            document.getElementById('dragDropContainer').style.visibility = 'hidden';
            document.getElementById('previewContainer').style.visibility = 'hidden';
            document.getElementById('uploadAnother').style.visibility = 'hidden';
            document.getElementById('loadingState').style.display = 'flex';
            document.getElementById('loadingState').style.visibility = 'visible';
}


//Function for Upload another file
function resetUpload() {
    const dragDropContainer = document.getElementById('dragDropContainer');
    const previewContainer = document.getElementById('previewContainer');
    const uploadAnotherBtn = document.getElementById('uploadAnother');
    const fileInput = document.getElementById('receipt_file');
    const previewImage = document.getElementById('preview');
    const loadingState = document.getElementById('loadingState');
   
    dragDropContainer.style.display = 'flex';
    dragDropContainer.style.visibility = 'visible';
    previewContainer.style.display = 'none';
    previewContainer.style.visibility = 'hidden';
    uploadAnotherBtn.style.display = 'none';
    uploadAnotherBtn.style.visibility = 'hidden';
    previewImage.src = '#';
    fileInput.value = '';
    loadingState.style.display = 'none';
    loadingState.style.visibility = 'hidden';

    // To clear the input fields once Upload Another File button is clicked:
    const formContainer = document.querySelector('.col-md-8 .row');

    if (formContainer){
        // Clear text inputs but exclude file input 
        const inputs = formContainer.querySelectorAll('input:not([type="file"])');
        inputs.forEach(input => {
            input.value = '';
        });
    }

    // To reset dropdowns
     const selects = formContainer.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });

        // To clear previous error messages
        const errorBoxes = formContainer.querySelectorAll('.error-message, .error-message1');
        errorBoxes.forEach(errorBox => {
            errorBox.textContent = '';
        });

         // Clear PHP session message
    const phpMessage = document.querySelector('.alert');
    if (phpMessage) {
        phpMessage.style.display = 'none';
        phpMessage.style.visibility = 'hidden';
    }

}
// Function for the drag and drop of Scan/Upload Forn
document.addEventListener('DOMContentLoaded', () => {
    // Drag-and-drop event listeners
    document.getElementById('dragDropContainer').addEventListener('dragover', (e) => {
        e.preventDefault();
        e.target.style.backgroundColor = '#e1e1e1';
    });


    document.getElementById('dragDropContainer').addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.target.style.backgroundColor = '';
        e.target.style.border = '';
    });

    document.getElementById('dragDropContainer').addEventListener('drop', (e) => {
        e.preventDefault();
        e.target.style.backgroundColor = '';
        e.target.style.border = '';
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            document.getElementById('receipt_file').files = files;
            previewImage(document.getElementById('receipt_file'));
        } else {
            document.getElementById('errorBoxfile').textContent = 'Please upload an image file.';
        }
    });
});
// For CLear button of input fields
function clearForm(button) {
    const row = button.closest('.row') || button.parentElement.parentElement;
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


    // Reset receipt preview section
    const dragDrop = document.getElementById('dragDropContainer');
    const preview = document.getElementById('previewContainer');
    const uploadBtn = document.getElementById('uploadAnother');
    const img = document.getElementById('preview');

    if (dragDrop && preview && uploadBtn && img) {
        dragDrop.style.display = 'flex';
        dragDrop.style.visibility = 'visible';
        preview.style.display = 'none';
        uploadBtn.style.display = 'none';
        img.src = '#';

        // Also clear the file input manually
        const receiptInput = document.getElementById('receipt_file');
        if (receiptInput) receiptInput.value = '';
    }

    // Clear file upload error message outside the row
    const globalFileError = document.getElementById('errorBoxfile');
    if (globalFileError) globalFileError.textContent = '';

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
    // Convert anything to string safely
    if (typeof text !== "string") {
        text = String(text || "");
    }

    text = text.trim();

    if (text.length < 3) return true;
    if (/^[0-9!@#$%^&*()_+=\-\[\]{};:'",.<>/?\\|]+$/.test(text)) return true;
    if (!/[aeiouAEIOU]/.test(text)) return true;
    if (/(.)\1{2,}/.test(text)) return true;
    if (/[bcdfghjklmnpqrstvwxyz]{5,}/i.test(text)) return true;

    if (/^[A-Za-z0-9]+$/.test(text) && /[A-Za-z]+/.test(text) && /\d+/.test(text)) {
        return true;
    }

    return false;
}
