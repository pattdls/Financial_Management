// This script handles the date selection and validation for the project form
document.addEventListener("DOMContentLoaded", function () {
  const startDateInput = document.getElementById("start_date");
  const endDateInput = document.getElementById("end_date");
  
  // Initialize Flatpickr for start date input
  flatpickr(startDateInput, {
    dateFormat: "Y-m-d",
    minDate: "2021-01-01",
    static: true,
    onChange: function (selectedDates, dateStr) {
      if (dateStr && selectedDates.length > 0) {
        // Clear the end date when a new start date is selected
        endDateInput.value = "";
        endDatePicker.clear();
        
        // Set minDate to one day after the selected start date
        const nextDay = new Date(selectedDates[0]);
        // nextDay.setDate(nextDay.getDate() + 1);
        endDatePicker.set("minDate", nextDay);
      }
    },
  });
  
  // Initialize Flatpickr for end date input
  const endDatePicker = flatpickr(endDateInput, {
    dateFormat: "Y-m-d",
    minDate: "today",
    static: true,
  });
});

// This script handles the P.O. Number validation to enforce "PO-001" format and uniqueness per client
// document.addEventListener("DOMContentLoaded", function () {
//   const poInput = document.querySelector('input[name="po_num"]');
//   const poWarning = document.createElement("small");
//   poWarning.className = "form-text text-danger";
//   poWarning.style.display = "none";
//   poInput.parentNode.appendChild(poWarning);

//   poInput.addEventListener("input", function () {
//     let value = poInput.value.toUpperCase();

//     // Only allow format: PO-001, PO-123, etc.
//     if (!/^PO-\d{3}$/.test(value)) {
//       // poWarning.textContent = "Format must be PO- followed by 3 digits (e.g. PO-001)";
//       // poWarning.style.display = "block";
//       // poInput.setCustomValidity("Invalid format");
//     } else {
//       // Check uniqueness
//       const clientId = document.querySelector('input[name="client_id"]').value;

//       fetch(
//         `../forms_logic/check_po_num.php?po_num=${value}&client_id=${clientId}`
//       )
//         .then((response) => response.json())
//         .then((data) => {
//           if (data.exists) {
//             poWarning.textContent =
//               "This P.O Number already exists for this client.";
//             poWarning.style.display = "block";
//             poInput.setCustomValidity("Duplicate PO Number");
//           } else {
//             poWarning.style.display = "none";
//             poInput.setCustomValidity("");
//           }
//         });
//     }
//     poInput.value = value; // force uppercase
//   });
// });

// MAIN Add Project Allocated Budget Validation
document.addEventListener("DOMContentLoaded", function () {
  const materials = document.getElementById("materials_cost");
  const labor = document.getElementById("labor_cost");
  const other = document.getElementById("other_expenses_cost");

  const labels = {
    materials: {
      input: materials,
      label: document.getElementById("materials_label"),
      text: "Materials Cost",
    },
    labor: {
      input: labor,
      label: document.getElementById("labor_label"),
      text: "Labor Cost",
    },
    other: {
      input: other,
      label: document.getElementById("other_label"),
      text: "Other Expenses Cost",
    },
  };
});

// Sum of Materials, Labor, and Other expenses cost for Allocated Budget Cost
const materialsInput = document.getElementById("materials_cost");
const laborInput = document.getElementById("labor_cost");
const otherInput = document.getElementById("other_expenses_cost");
const allocatedBudgetInput = document.getElementById("allocated_budget");
const projectCostInput = document.getElementById("project_cost");
const budgetWarning = document.getElementById("budgetWarning");

let budgetExceeded = false;

function updateAllocatedBudget() {
  const materials = parseFloat(materialsInput.value) || 0;
  const labor = parseFloat(laborInput.value) || 0;
  const other = parseFloat(otherInput.value) || 0;
  const projectCost = parseFloat(projectCostInput.value) || 0;

  const totalAllocated = materials + labor + other;
  allocatedBudgetInput.value = totalAllocated.toFixed(2);

  // Validation for exceeding allocated budget cost to project cost
  if (projectCost > 0 && totalAllocated > projectCost) {
    budgetWarning.style.display = "block";
    budgetWarning.textContent =
      "Total Allocated Budget Cost exceeds the Project Cost.";
      allocatedBudgetInput.classList.add("is-invalid"); // Add red border
    budgetExceeded = true;
  } else {
    budgetWarning.style.display = "none";
    allocatedBudgetInput.classList.remove("is-invalid") // Remove red border
    budgetExceeded = false;
  }
}

[materialsInput, laborInput, otherInput, projectCostInput].forEach((input) => {
  input.addEventListener("input", updateAllocatedBudget);
});

// Prevent form submission if budget exceeded
// document.querySelectorAll("form").forEach((form) => {
//   form.addEventListener("submit", function (e) {
//     if (budgetExceeded) {
//       e.preventDefault();
//       alert(
//         "Cannot submit: Allocated Budget Cost exceeds the Project Cost."
//       );
//     }
//   });
// });

// This script handles budget input validation and prevents negative values 1 - FIXED
document.addEventListener("DOMContentLoaded", function () {
  const budgetInputs = document.querySelectorAll(".budget-input");

  budgetInputs.forEach(function (input) {
    const warning = input
      .closest(".input-group")
      .parentElement.querySelector(".budget-warning");

    input.addEventListener("input", function (e) {
      // Store cursor position
      const cursorPosition = input.selectionStart;
      let value = input.value;
      const originalLength = value.length;

      // Remove any characters that aren't digits or decimal points
      value = value.replace(/[^0-9.]/g, "");

      // Handle multiple decimal points - keep only first one
      // const decimalIndex = value.indexOf('.');
      // if (decimalIndex !== -1) {
      //   value = value.substring(0, decimalIndex + 1) + value.substring(decimalIndex + 1).replace(/\./g, '');
      // }

      // Only update if value actually changed
      if (input.value !== value) {
        input.value = value;

        // Restore cursor position, adjusting for any removed characters
        const lengthDifference = originalLength - value.length;
        const newPosition = Math.max(0, cursorPosition - lengthDifference);
        input.setSelectionRange(newPosition, newPosition);
      }

      // Clear any browser validation errors for step mismatch
      input.setCustomValidity("");

      const numericValue = parseFloat(value);

      if (
        value === "" ||
        isNaN(numericValue) ||
        numericValue < 1000 ||
        numericValue < 0
      ) {
        warning.style.display = "block";
        input.classList.add("is-invalid");
      } else {
        warning.style.display = "none";
        input.classList.remove("is-invalid");
      }
    });

    // Handle paste events
    input.addEventListener("paste", function (e) {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData("text");
      const cleanedPaste = paste.replace(/[^0-9.]/g, "");

      if (cleanedPaste) {
        const cursorPosition = input.selectionStart;
        const currentValue = input.value;
        const newValue =
          currentValue.slice(0, cursorPosition) +
          cleanedPaste +
          currentValue.slice(input.selectionEnd);

        // Apply the same cleaning logic
        let finalValue = newValue.replace(/[^0-9.]/g, "");
        const decimalIndex = finalValue.indexOf(".");
        if (decimalIndex !== -1) {
          finalValue =
            finalValue.substring(0, decimalIndex + 1) +
            finalValue.substring(decimalIndex + 1).replace(/\./g, "");
          const parts = finalValue.split(".");
          if (parts[1] && parts[1].length > 2) {
            finalValue = parts[0] + "." + parts[1].substring(0, 2);
          }
        }

        input.value = finalValue;
        input.dispatchEvent(new Event("input"));
      }
    });
  });
});

// This script handles budget input validation and prevents negative values 2 - FIXED
document.addEventListener("DOMContentLoaded", function () {
  const budgetInputs = document.querySelectorAll(".budget2-input");

  budgetInputs.forEach(function (input) {
    input.addEventListener("input", function (e) {
      // Store cursor position
      const cursorPosition = input.selectionStart;
      let value = input.value;
      const originalLength = value.length;

      // Remove any characters that aren't digits or decimal points
      value = value.replace(/[^0-9.]/g, "");

      // Handle multiple decimal points - keep only the first one
      const decimalIndex = value.indexOf(".");
      if (decimalIndex !== -1) {
        value =
          value.substring(0, decimalIndex + 1) +
          value.substring(decimalIndex + 1).replace(/\./g, "");
      }

      // Only update if value actually changed
      if (input.value !== value) {
        input.value = value;

        // Restore cursor position, adjusting for any removed characters
        const lengthDifference = originalLength - value.length;
        const newPosition = Math.max(0, cursorPosition - lengthDifference);
        input.setSelectionRange(newPosition, newPosition);
      }
    });

    // Handle paste events
    input.addEventListener("paste", function (e) {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData("text");
      const cleanedPaste = paste.replace(/[^0-9.]/g, "");

      if (cleanedPaste) {
        const cursorPosition = input.selectionStart;
        const currentValue = input.value;
        const newValue =
          currentValue.slice(0, cursorPosition) +
          cleanedPaste +
          currentValue.slice(input.selectionEnd);

        // Apply the same cleaning logic
        let finalValue = newValue.replace(/[^0-9.]/g, "");
        const decimalIndex = finalValue.indexOf(".");
        if (decimalIndex !== -1) {
          finalValue =
            finalValue.substring(0, decimalIndex + 1) +
            finalValue.substring(decimalIndex + 1).replace(/\./g, "");
          const parts = finalValue.split(".");
          if (parts[1] && parts[1].length > 2) {
            finalValue = parts[0] + "." + parts[1].substring(0, 2);
          }
        }

        input.value = finalValue;
        input.dispatchEvent(new Event("input"));
      }
    });
  });
});

// Project add-on allocated budget validation
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".project-addon-modal").forEach(function (modal) {
    const allocatedInput = modal.querySelector(".addon-allocated-budget");
    const materialsInput = modal.querySelector(".addon-materials-cost");
    const laborInput = modal.querySelector(".addon-labor-cost");
    const otherInput = modal.querySelector(".addon-other-expenses-cost");
    const projectCostInput = modal.querySelector(".addon-project-cost"); 
    const budgetWarning = modal.querySelector(".addon-budget-warning");

    let budgetExceeded = false;

    function updateAllocatedBudget() {
      const materials = parseFloat(materialsInput.value) || 0;
      const labor = parseFloat(laborInput.value) || 0;
      const other = parseFloat(otherInput.value) || 0;
      const projectCost = parseFloat(projectCostInput?.value) || 0;

      const totalAllocated = materials + labor + other;
      allocatedInput.value = totalAllocated > 0 ? totalAllocated.toFixed(2) : "";

      // Validation for exceeding project cost
      if (projectCost > 0 && totalAllocated > projectCost) {
        budgetWarning.style.display = "block";
        budgetWarning.textContent =
          "Total Allocated Budget Cost exceeds the Project Cost!";
        budgetExceeded = true;
      } else if (totalAllocated > 0 && totalAllocated < 1000) {
        budgetWarning.style.display = "block";
        budgetWarning.textContent =
          "Total Allocated Budget Cost cannot be less than ₱1,000.";
        budgetExceeded = true;
      } else {
        budgetWarning.style.display = "none";
        budgetExceeded = false;
      }
    }

    // Watch all inputs
    [materialsInput, laborInput, otherInput, projectCostInput].forEach((input) => {
      if (input) {
        input.addEventListener("input", updateAllocatedBudget);
      }
    });

    // Prevent form submission if budget is invalid
    // modal.querySelectorAll("form").forEach((form) => {
    //   form.addEventListener("submit", function (e) {
    //     if (budgetExceeded) {
    //       e.preventDefault();
    //       alert(
    //         "Cannot submit: Allocated Budget Cost is invalid (either exceeds Project Cost or is below ₱1,000)."
    //       );
    //     }
    //   });
    // });

    // Initial validation
    updateAllocatedBudget();
  });
});

// Date and Time Function
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  };

  document.getElementById("datetime").innerHTML = now.toLocaleDateString(
    "en-US",
    options
  );
}

// Update every second
setInterval(updateDateTime, 1000);

// Initial call to display time immediately
updateDateTime();

// Initialize Select2 for the province, city, and barangay dropdowns
document.addEventListener("DOMContentLoaded", function () {
  const provinceSelect = document.getElementById("province");
  const citySelect = document.getElementById("city");
  const barangaySelect = document.getElementById("barangay");

  // Load Provinces on page load
  fetch("https://psgc.cloud/api/provinces")
    .then((res) => res.json())
    .then((data) => {
      // Sort provinces alphabetically
      data.sort((a, b) => a.name.localeCompare(b.name));

      // Append provinces to the dropdown
      data.forEach((province) => {
        const opt = document.createElement("option");
        opt.name = province.code; // Use province code as the value
        opt.textContent = province.name; // Display province name
        provinceSelect.appendChild(opt);
      });
    });

  // Load Cities/Municipalities when Province is selected
  provinceSelect.addEventListener("change", function () {
    const provinceCode = this.value; // Get the selected province code

    if (provinceCode) {
      // Fetch cities/municipalities for the selected province
      fetch(
        `https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`
      )
        .then((res) => res.json())
        .then((data) => {
          // Sort cities/municipalities alphabetically
          data.sort((a, b) => a.name.localeCompare(b.name));

          // Append cities/municipalities to the dropdown
          data.forEach((city) => {
            const opt = document.createElement("option");
            opt.name = city.code; // Use city code as the value
            opt.textContent = city.name; // Display city/municipality name
            citySelect.appendChild(opt);
          });
        });
    }
  });

  // Load Barangays when City/Municipality is selected
  citySelect.addEventListener("change", function () {
    const cityCode = this.value; // Get the selected city code

    if (cityCode) {
      // Fetch barangays for the selected city/municipality
      fetch(
        `https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`
      )
        .then((res) => res.json())
        .then((data) => {
          // Sort barangays alphabetically
          data.sort((a, b) => a.name.localeCompare(b.name));

          // Append barangays to the dropdown
          data.forEach((brgy) => {
            const opt = document.createElement("option");
            opt.name = brgy.name; // Use barangay name as the value
            opt.textContent = brgy.name; // Display barangay name
            barangaySelect.appendChild(opt);
          });
        });
    }
  });
});

// Contract Preview Functionality for both Main Project and Add-ons
document.addEventListener("DOMContentLoaded", () => {
  // Get all project addon modals
  const projectModals = document.querySelectorAll(".project-addon-modal");

  projectModals.forEach((modal) => {
    // Extract project ID from modal ID (e.g., "projectAddon123" -> "123")
    const projectId = modal.id.replace("projectAddon", "");

    // Get elements for this specific project
    const fileInput = document.getElementById(`project_file_${projectId}`);
    const previewModalEl = document.getElementById(
      `previewContract${projectId}`
    );
    const previewImage = document.getElementById(`previewImage_${projectId}`);
    const previewPDF = document.getElementById(`previewPDF_${projectId}`);
    const previewFileName = document.getElementById(
      `previewFileName_${projectId}`
    );
    const cancelBtn = document.getElementById(`cancelFile_${projectId}`);
    const confirmBtn = document.getElementById(`confirmFile_${projectId}`);

    // Skip if any required elements are missing
    if (
      !fileInput ||
      !previewModalEl ||
      !previewImage ||
      !previewPDF ||
      !previewFileName ||
      !cancelBtn ||
      !confirmBtn
    ) {
      console.warn(`Missing elements for project ${projectId}`);
      return;
    }

    // Initialize Bootstrap modal
    const previewModal = new bootstrap.Modal(previewModalEl, {
      backdrop: "static",
      keyboard: false,
    });

    let currentFile = null;

    // File input change handler
    fileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (!file) return;

      // Validate file type
      const validTypes = [
        "application/pdf",
        "image/png",
        "image/jpeg",
        "image/jpg",
      ];
      if (!validTypes.includes(file.type)) {
        alert("Please select a PDF or image file (PNG, JPEG).");
        this.value = "";
        return;
      }


      currentFile = file;
      previewFileName.textContent = file.name;

      // Reset preview elements
      previewImage.classList.add("d-none");
      previewImage.src = "";
      previewPDF.classList.add("d-none");
      previewPDF.src = "";

      // Read and display file
      const reader = new FileReader();
      reader.onload = function (event) {
        try {
          if (file.type === "application/pdf") {
            previewPDF.src = event.target.result;
            previewPDF.classList.remove("d-none");
          } else if (file.type.startsWith("image/")) {
            previewImage.src = event.target.result;
            previewImage.classList.remove("d-none");
          }

          // Show preview modal
          previewModal.show();
        } catch (error) {
          console.error("Error loading file preview:", error);
          alert("Error loading file preview. Please try again.");
        }
      };

      reader.onerror = function () {
        alert("Error reading file. Please try again.");
        fileInput.value = "";
        currentFile = null;
      };

      reader.readAsDataURL(file);
    });

    // Cancel button handler
    cancelBtn.addEventListener("click", function () {
      // Clear file input
      fileInput.value = "";
      currentFile = null;

      // Reset preview elements
      previewImage.src = "";
      previewImage.classList.add("d-none");
      previewPDF.src = "";
      previewPDF.classList.add("d-none");
      previewFileName.textContent = "";

      // Hide modal
      previewModal.hide();
    });

    // Confirm button handler
    confirmBtn.addEventListener("click", function () {
      // Just hide the modal, keep the file
      previewModal.hide();
      currentFile = null;
    });

    // Clean up when preview modal is hidden
    previewModalEl.addEventListener("hidden.bs.modal", function () {
      // Reset preview elements
      previewImage.src = "";
      previewImage.classList.add("d-none");
      previewPDF.src = "";
      previewPDF.classList.add("d-none");
    });
  });

  // Also handle the main project form file upload (if it exists)
  const mainProjectFileInput = document.getElementById("project_file_main");
  const previewModalElMain = document.getElementById("previewContractMain");
  const previewImageMain = document.getElementById("previewImage_main");
  const previewPDFMain = document.getElementById("previewPDF_main");
  const previewFileNameMain = document.getElementById("previewFileName_main");
  const cancelBtnMain = document.getElementById("cancelFile_main");
  const confirmBtnMain = document.getElementById("confirmFile_main");

  if (
    mainProjectFileInput &&
    previewModalElMain &&
    previewImageMain &&
    previewPDFMain &&
    previewFileNameMain &&
    cancelBtnMain &&
    confirmBtnMain
  ) {
    const previewModalMain = new bootstrap.Modal(previewModalElMain, {
      backdrop: "static",
      keyboard: false,
    });

    let currentFileMain = null;

    mainProjectFileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (!file) return;

      const validTypes = ["application/pdf", "image/png", "image/jpeg", "image/jpg"];
      if (!validTypes.includes(file.type)) {
        alert("Please select a PDF or image file (PNG, JPEG).");
        this.value = "";
        return;
      }

      currentFileMain = file;
      previewFileNameMain.textContent = file.name;

      // Reset previews
      previewImageMain.classList.add("d-none");
      previewPDFMain.classList.add("d-none");

      const reader = new FileReader();
      reader.onload = function (event) {
        if (file.type === "application/pdf") {
          previewPDFMain.src = event.target.result;
          previewPDFMain.classList.remove("d-none");
        } else if (file.type.startsWith("image/")) {
          previewImageMain.src = event.target.result;
          previewImageMain.classList.remove("d-none");
        }
        previewModalMain.show();
      };
      reader.readAsDataURL(file);
    });

    cancelBtnMain.addEventListener("click", function () {
      mainProjectFileInput.value = "";
      currentFileMain = null;
      previewImageMain.src = "";
      previewPDFMain.src = "";
      previewFileNameMain.textContent = "";
      previewModalMain.hide();
    });

    confirmBtnMain.addEventListener("click", function () {
      previewModalMain.hide();
      currentFileMain = null;
    });

    previewModalElMain.addEventListener("hidden.bs.modal", function () {
      previewImageMain.src = "";
      previewPDFMain.src = "";
    });
  }
});