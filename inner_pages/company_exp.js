

document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector('form[name="companyExpenseForm"]');
  const previewBtn = document.getElementById("previewBeforeSave");
  const finalSubmitBtn = document.getElementById("finalSubmit");
  let tempFormData = null;

  
    previewBtn.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent default form submission


      const errorBox1 = document.getElementById("errorBoxdes");
      const errorBox3 = document.getElementById("errorBoxamount");
      const errorBox4 = document.getElementById("errorBoxinvoice");
      const errorBox5 = document.getElementById("errorBoxfile");

      errorBox1.innerText = "";
      errorBox3.innerText = "";
      errorBox4.innerHTML = "";
      errorBox5.innerHTML = "";

      //To store errors for showing all together with backend 
        let frontendErrors = {
            description: "",
            amount: ""
        }

      // Validate Description and Store fields
      const description = document.getElementById("description").value.trim();
      const amount = document.getElementById("amount").value.trim();

      const wordCount = (str) =>
        str
          .replace(/[^\w\s]|_/g, "")
          .split(/\s+/)
          .filter((w) => w).length;

        if (isGibberish(description)){
             frontendErrors.description = "Please input valid description.";
        }
        else if (wordCount(description) < 1 || wordCount(description) > 10) {
            frontendErrors.description = "Expense Description must contain at least one word but not greater than ten words.";
            
        }

      const filteredAmount = amount.replace(/,/g, ""); //Removes the comma
      const hasSpace = /\s/.test(filteredAmount);
      const isNumber = !isNaN(filteredAmount) && filteredAmount !== "";


      //Condition to check if amount is valid
      if (!isNumber || hasSpace || Number(filteredAmount) < 5 || filteredAmount < 0) {
        frontendErrors.amount = "Amount must be a valid number, greater than 5 pesos, and must not include spaces.";
      }

      // If all validations passed, only then will validate for duplicating files
      tempFormData = new FormData(form);
      tempFormData.append('save_company_expense', '1');
      tempFormData.append('validate_only_company', '1'); // This is to signal the backend to not insert the data yet
    
    showSavingLoading();
      fetch("expense_logic.php", {
        method: "POST",
        body: tempFormData,
      })
        .then((response) => response.json())
        .then((data) => {
            hideSavingLoading();
            // Show frontend errors
            errorBox1.innerHTML = frontendErrors.description || "";
            errorBox3.innerHTML = frontendErrors.amount || "";

            // Show backend errors
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

            tempFormData.set('validate_only_company', '0'); // This tells backend to insert into DB

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