document.addEventListener("DOMContentLoaded", function () {
    // This will be triggered when the page is fully loaded
    const clientDropdown = document.getElementById("client-dropdown");
    const projectDropdown = document.getElementById("project-dropdown");

    // Function to update projects based on selected client
    function updateProjects(clientID) {
        if (clientID === "") {
            $(projectDropdown).empty().trigger("change");
            projectDropdown.disabled = true;
            return;
        }
        console.log("Fetching projects for client ID:", clientID);  // Debugging line

       fetch(`expense_logic.php?client_id=${clientID}&form=payment`)
    .then(response => response.json())
    .then(projects => {
        console.log("Projects received:", projects);

        if (projects.length === 0) {
            projectDropdown.innerHTML = '<option value="">No projects found</option>';
            projectDropdown.disabled = true;
            return;
        }
         
        let options = `<option value="">Select Project</option>`;
        
        projects.forEach(p => {
            if (p.type === "main" && p.project_status === "Ongoing") {
                options += `<option value="${p.project_id}" data-type="main" data-projectid="${p.project_id}">${p.project_name}</option>`;
            } 
            if (p.type === "addon" && p.project_status === "Ongoing") {
                options += `<option value="${p.addon_id}" data-type="addon" data-mainid="${p.main_project_id}" data-mainname="${p.main_project_name}" 
                data-addonid="${p.addon_id}">${p.addon_name}</option>`;
            }

        });

        projectDropdown.innerHTML = options;
        projectDropdown.disabled = false;

        // Initialize Select2
        $(projectDropdown).select2({
            placeholder: "Select Project",
            templateResult: formatProjectAddon,
            templateSelection: formatProjectAddon,
            minimumResultsForSearch: Infinity,
            dropdownCssClass: 'bootstrap-select', 
            selectionCssClass: 'form-select bic',
            dropdownAutoWidth: true,
             width: '200px'
        });
        // Manually trigger placeholder display
        $('#project-dropdown').val('').trigger('change');

        // Set hidden inputs on change
        $('#project-dropdown').on('change', function () {
            const selectedOption = $(projectDropdown).find(':selected');
            if (selectedOption.length) {
                const type = selectedOption.data('type');
                if(type === "main") {
                    $('#main_project_id').val(selectedOption.data('projectid'));
                    $('#addon_id').val("");
                } else if(type === "addon") {
                    $('#main_project_id').val(selectedOption.data('mainid'));
                    $('#addon_id').val(selectedOption.data('addonid'));
                }
            }
            console.log("Main project id:", $('#main_project_id').val());
        console.log("Addon id:", $('#addon_id').val());
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

//Date validation
// document.addEventListener("DOMContentLoaded", function () {
//     const dateField = document.getElementById("expense-date");
//     const today = new Date();
//     const minDate = new Date();
//     minDate.setDate(today.getDate() - 14);


//     // Show modal immediately if user selects an invalid date
//     dateField.addEventListener("change", function () {
//         const selectedDate = new Date(dateField.value);

//         selectedDate.setHours(0, 0, 0, 0);
//         today.setHours(0, 0, 0, 0);
//         minDate.setHours(0, 0, 0, 0);

//         if (selectedDate < minDate || selectedDate > today) {
//             showDateErrorModal();
//             dateField.value = ""; // Clear the invalid value
//         }
//     });


//     function showDateErrorModal() {
//     //This will be used to display the allowed date when modal appears
//     const today = new Date();
//     const minDate = new Date();
//     minDate.setDate(today.getDate() - 14);

//     //Turns numeric format of date to word
//     const dateFormat = { year: 'numeric', month: 'long', day: 'numeric' };
//     const namedToday   = today.toLocaleDateString('en-US', dateFormat);
//     const namedMinDate = minDate.toLocaleDateString('en-US', dateFormat);

//     //Error message that will populate the div in the HTML
//     const modalBody = document.querySelector("#staticBackdrop .modal-body");
//     modalBody.innerHTML = `
//         <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 70px; margin-bottom: 50px;"></i>
//         <h4 class="mb-0" style="margin-top: -5px;">Invalid Date!</h4>
//         <p class="pt-3">
//             The date you selected is not within the allowed range.<br>
//             Please choose a date between <strong>${namedMinDate}</strong> and <strong>${namedToday}</strong>.                                
//         </p>
//         <div class="modal-footer border-0 d-flex justify-content-center">
//             <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
//         </div>
//     `;

//     const errorModal = new bootstrap.Modal(document.getElementById("staticBackdrop"));
//     errorModal.show();
//     }
// });

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[name="expenseForm"]');
    const previewBtn = document.getElementById("previewBeforeSave");
    const finalSubmitBtn = document.getElementById("finalSubmit");
    let tempFormData = null;

    previewBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default form submission

        const errorBox1 = document.getElementById("errorBoxdes");
        const errorBox3 = document.getElementById("errorBoxamount");
        const errorBox4 = document.getElementById("errorBoxinvoice");
        const errorBox5 = document.getElementById("errorBoxfile");

        errorBox1.innerHTML = "";
        errorBox3.innerHTML = "";
        errorBox4.innerHTML = "";
        errorBox5.innerHTML = "";

        //To store errors for showing all together with backend 
        let frontendErrors = {
            description: "",
            amount: ""
        };

        // Validate Description field
        const description = document.getElementById("description").value.trim();
        const amount = document.getElementById("amount").value.trim();

        const wordCount = str => str.replace(/[^\w\s]|_/g, "").split(/\s+/).filter(w => w).length;
        
        //To limit input to 10 words only
        if (isGibberish(description)){
             frontendErrors.description = "Please input valid description.";
        }
        else if (wordCount(description) < 1 || wordCount(description) > 10) {
            frontendErrors.description = "Expense Description must contain at least one word but not greater than ten words.";
            
        }

        const filteredAmount = amount.replace(/,/g, ''); //Removes the comma
        const hasSpace = /\s/.test(filteredAmount); 
        const isValidNumber = /^(?!-)[0-9]+(\.[0-9]+)?$/.test(filteredAmount);

        //Condition to check if amount is valid
        if (!isValidNumber || hasSpace || Number(filteredAmount) < 5)  {
           frontendErrors.amount = "Amount must be a valid number, greater than 5 pesos, and must not include spaces.";
            
        }

        tempFormData = new FormData(form);
        tempFormData.append('save_client_payment', '1');
        tempFormData.append('validate_only_payment', '1'); 
        
        showSavingLoading();
        fetch('expense_logic.php', {
            method: 'POST',
            body: tempFormData
        })
        .then(response => response.json()) 
        .then(data => {
            hideSavingLoading();
            // Display front-end errors
            errorBox1.innerHTML = frontendErrors.description || "";
            errorBox3.innerHTML = frontendErrors.amount || "";
                // Display back-end errors
            if (data.errors) {
                if (data.errors.invoice) errorBox4.innerHTML = data.errors.invoice;
                if (data.errors.file) errorBox5.innerHTML = data.errors.file;
                if (data.errors.file_invalid) errorBox5.innerHTML = data.errors.file_invalid;
                if (data.errors.file_too_large) errorBox5.innerHTML = data.errors.file_too_large;
            } 
            
            // Determine if there are frontend/backend errors
            const hasFrontendError = frontendErrors.description !== "" || frontendErrors.amount !== "";
            const hasBackendError = data.errors && (
              data.errors.invoice || data.errors.file || data.errors.file_invalid || data.errors.file_too_large
            );

            // Show modal only if no errors
            if (!hasFrontendError && !hasBackendError && data.status === 'success') {
              const modal = new bootstrap.Modal(document.getElementById('confirmSubmitForm'));
              modal.show();
            } else if (data.status === 'error') {
              showAlertMessage(data.message, 'danger');
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

            tempFormData.set('validate_only_payment', '0'); // This tells backend to insert into DB
            
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

document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('#payment-method');
    const otherInput = document.querySelector('#other-payment-input');

    // Handle dropdown change
    dropdown.addEventListener('change', function() {
        if (this.value === 'Others') {
            otherInput.style.display = 'block';
            otherInput.required = true;
        } else {
            otherInput.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    });

    // Handle form submission
    document.querySelector('#expense-form').addEventListener('submit', function(event) {
        if (dropdown.value === 'Others' && otherInput.value.trim() !== '') {
            // Disable select to prevent it from being submitted
            dropdown.name = '';
            // Set input name to payment_method to submit its value
            otherInput.name = 'payment_method';
        } else {
            // Restore select name and remove input name
            dropdown.name = 'payment_method';
            otherInput.name = '';
        }
    });
});
// For CLear button of input fields
function clearForm(button) {
    const formWrapper = document.getElementById('expense-form');
    const inputs = formWrapper.querySelectorAll('input');
    const selects = formWrapper.querySelectorAll('select');
    const errorBoxes = formWrapper.querySelectorAll('.error-message, .error-message1');

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


        // Also clear the file input manually
        const receiptInput = document.getElementById('receipt_file');
        if (receiptInput) receiptInput.value = '';
    

    // Clear file upload error message outside the row
    const globalFileError = document.getElementById('errorBoxfile');
    if (globalFileError) globalFileError.textContent = '';

      //  Hide the 'other payment method' input if shown
    const otherPayment = formWrapper.querySelector('#other-payment-input');
    if (otherPayment) {
        otherPayment.style.display = 'none';
        otherPayment.value = '';
    }
     const phpMessage = document.getElementById('phpStatusMessage');
    if (phpMessage) {
        phpMessage.remove();
    }

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
