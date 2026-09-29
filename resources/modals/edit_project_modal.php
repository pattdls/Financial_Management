<!-- Edit Project Modal - FIXED VERSION -->
<div class="modal fade" id="editProjectModal<?php echo $row['project_id']; ?>" tabindex="-1" aria-labelledby="editProjectLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectLabel">Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- CRITICAL: Make sure the form action points to edit_project.php and has the correct method -->
                <form action="../forms_logic/edit_project.php" method="POST" id="editProjectForm<?php echo $row['project_id']; ?>">
                    <!-- CRITICAL: These hidden fields must be present and correct -->
                    <input type="hidden" name="client_id" value="<?php echo $row['client_id']; ?>">
                    <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                    
                    <div class="row mb-3">
                        <!-- Project Name -->
                        <div class="col-md-9">
                            <label class="form-label">Project Name<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['project_name']); ?>" name="project_name" required>
                        </div>
                        <!-- P.O Number -->
                        <div class="col-md-3">
                            <label class="form-label">P.O Number<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['po_num']); ?>" name="po_num" id="edit_po_num_<?php echo $row['project_id']; ?>" required>
                            <small id="poNumWarning_<?php echo $row['project_id']; ?>" class="form-text text-danger" style="display:none;"></small>
                        </div>
                    </div>
                    
                    <!-- Project Dates -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date_<?php echo $row['project_id']; ?>" class="form-label">Start Date <span style="color: red;">*</span></label>
                            <input type="text" class="form-control start-date" id="start_date_<?php echo $row['project_id']; ?>" value="<?php echo $row['start_date']; ?>" name="start_date" required placeholder="Please select a start date.">
                            <small class="form-text text-muted" style="font-size: 0.7em;">Please select a start date within the next 6 months.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date_<?php echo $row['project_id']; ?>" class="form-label">End Date <span style="color: red;">*</span></label>
                            <input type="text" class="form-control end-date" value="<?php echo $row['end_date']; ?>" id="end_date_<?php echo $row['project_id']; ?>" name="end_date" required placeholder="Please select an end date.">
                        </div>
                    </div>
                    
                    <!-- Project Cost -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Cost<span style="color: red;">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fs-4">₱</span>
                                <input
                                    type="number"
                                    class="form-control fs-5 text-muted"
                                    value="<?php echo $row['projected_budget_cost']; ?>"
                                    name="projected_budget_cost"
                                    required
                                    placeholder="00.00"
                                    style="box-shadow: none;"
                                    id="budgetInput_<?php echo $row['project_id']; ?>"
                                    min="1000"
                                    step="0.01">
                            </div>
                            <small id="budgetWarning_<?php echo $row['project_id']; ?>" class="form-text text-danger" style="display:none;">
                                Project Cost must be at least ₱1,000.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Area<span style="color: red;">*</span></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['project_type']); ?>" name="project_type" required placeholder="Enter Project Type">
                        </div>
                    </div>

                    <!-- Allocated Budget -->
                    <p class="mb-2">Allocated Budget Cost<span style="color: red;">*</span></p>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text fs-4">₱</span>
                                <input type="number" class="form-control fs-5 text-muted" value="<?php echo $row['allocated_budget']; ?>" name="allocated_budget" required placeholder="00.00" style="box-shadow: none;" id="edit_allocated_budget_<?php echo $row['project_id']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cost Breakdown -->
                    <div class="row mb-4">
                        <!-- Materials Cost -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fs-4">₱</span>
                                <input type="number" class="form-control fs-5 text-muted" value="<?php echo $row['materials_cost']; ?>" name="materials_cost" required placeholder="00.00" style="box-shadow: none;" id="edit_materials_cost_<?php echo $row['project_id']; ?>">
                            </div>
                            <label class="form-label text-muted">Materials Cost</label>
                        </div>
                        <!-- Labor Cost -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fs-4">₱</span>
                                <input type="number" class="form-control fs-5 text-muted" value="<?php echo $row['labor_cost']; ?>" name="labor_cost" required placeholder="00.00" style="box-shadow: none;" id="edit_labor_cost_<?php echo $row['project_id']; ?>">
                            </div>
                            <label class="form-label text-muted">Labor Cost</label>
                        </div>
                        <!-- Other Expenses Cost -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text fs-4">₱</span>
                                <input type="number" class="form-control fs-5 text-muted" value="<?php echo $row['other_expenses_cost']; ?>" name="other_expenses_cost" required placeholder="00.00" style="box-shadow: none;" id="edit_other_expenses_cost_<?php echo $row['project_id']; ?>">
                            </div>
                            <label class="form-label text-muted">Other Expenses Cost</label>
                        </div>
                        <small id="editBudgetValidationWarning_<?php echo $row['project_id']; ?>" class="form-text text-danger" style="display: none; font-size: 0.875rem;">
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                            <!-- CRITICAL: Make sure the button name is exactly 'save_changes' -->
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="save_changes" class="btn btn-success" 
                                    onclick="console.log('Edit form submitted for project <?php echo $row['project_id']; ?>');">
                                Save Changes
                            </button>
                            
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Unique P.O. Number Validation
        <?php $pid = $row['project_id']; ?>
        const poInput = document.getElementById("edit_po_num_<?php echo $pid; ?>");
        const poWarning = document.getElementById("poNumWarning_<?php echo $pid; ?>");
        const clientId = "<?php echo $row['client_id']; ?>";
        const projectId = "<?php echo $row['project_id']; ?>";

        poInput.addEventListener("input", function() {
            const poNum = poInput.value;
            if (poNum) {
                fetch(`../forms_logic/check_po_num.php?po_num=${encodeURIComponent(poNum)}&client_id=${clientId}&exclude_project_id=${projectId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            poWarning.textContent = "This P.O Number already exists for this client.";
                            poWarning.style.display = "block";
                            poInput.classList.add("is-invalid");
                            poInput.setCustomValidity("Duplicate PO Number");
                        } else {
                            poWarning.textContent = "";
                            poWarning.style.display = "none";
                            poInput.classList.remove("is-invalid");
                            poInput.setCustomValidity("");
                        }
                    });
            } else {
                poWarning.textContent = "";
                poWarning.style.display = "none";
                poInput.classList.remove("is-invalid");
                poInput.setCustomValidity("");
            }
        });

    });

    // Date Picker Initialization
    document.addEventListener("DOMContentLoaded", function() {
        const startDateInput = document.getElementById("start_date_<?php echo $row['project_id']; ?>");
        const endDateInput = document.getElementById("end_date_<?php echo $row['project_id']; ?>");

        let endDatePicker;

        // Initialize Flatpickr for Start Date
        flatpickr(startDateInput, {
            dateFormat: "Y-m-d",
            minDate: "2021-01-01", // Start date cannot be in the past
            maxDate: new Date(new Date().setMonth(new Date().getMonth() + 6)),
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length) {
                    // clear any previously selected end date
                    endDateInput.value = "";
                    if (endDatePicker) {
                        endDatePicker.clear();

                        // set minDate to start date + 1 day so end cannot be same as start
                        const startDate = selectedDates[0];
                        const minEndDate = new Date(startDate.getTime() + 24 * 60 * 60 * 1000);
                        endDatePicker.set("minDate", minEndDate);
                    }
                }
            }
        });

        // Initialize Flatpickr for End Date
        endDatePicker = flatpickr(endDateInput, {
            dateFormat: "Y-m-d",
            // if a start date already exists, ensure end's minDate is start + 1 day; otherwise default to tomorrow
            minDate: (function() {
                if (startDateInput.value) {
                    const sd = new Date(startDateInput.value);
                    return new Date(sd.getTime() + 24 * 60 * 60 * 1000);
                }
                const t = new Date();
                return new Date(t.getTime() + 24 * 60 * 60 * 1000); // tomorrow
            })(),
            maxDate: new Date(new Date().setFullYear(new Date().getFullYear() + 1))
        });
    });

    // Budget Input Formatting and Validation
    document.addEventListener("DOMContentLoaded", function() {
        const projectId = "<?php echo $row['project_id']; ?>";

        const allocated = document.getElementById("edit_allocated_budget_" + projectId);
        const materials = document.getElementById("edit_materials_cost_" + projectId);
        const labor = document.getElementById("edit_labor_cost_" + projectId);
        const other = document.getElementById("edit_other_expenses_cost_" + projectId);
        const warning = document.getElementById("editBudgetValidationWarning_" + projectId);

        function validateEditBudget() {
            const allocatedVal = parseFloat(allocated.value) || 0;
            const materialsVal = parseFloat(materials.value) || 0;
            const laborVal = parseFloat(labor.value) || 0;
            const otherVal = parseFloat(other.value) || 0;

            fetch(`../forms_logic/check_edit_budget.php?allocated=${allocatedVal}&materials=${materialsVal}&labor=${laborVal}&other=${otherVal}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.isUnderBudget) {
                        warning.innerHTML = "<i class=\"bi bi-exclamation-triangle-fill\"></i>&nbsp;The sum of Materials, Labor, and Other Expenses cannot exceed the Allocated Budget Cost.";
                        warning.style.display = "block";

                        [allocated, materials, labor, other].forEach(el => {
                            el.classList.add("is-invalid");
                            el.setCustomValidity("Expenses exceed budget");
                        });
                    } else {
                        warning.textContent = "";
                        warning.style.display = "none";

                        [allocated, materials, labor, other].forEach(el => {
                            el.classList.remove("is-invalid");
                            el.setCustomValidity("");
                        });
                    }
                });
        }

        [allocated, materials, labor, other].forEach(input => {
            input.addEventListener("input", validateEditBudget);
        });
    });

    // Budget Input Validation for Project Cost
    // This script ensures that the project cost is at least 1000
    document.addEventListener("DOMContentLoaded", function() {
        const budgetInput = document.getElementById("budgetInput_<?php echo $row['project_id']; ?>");
        const budgetWarning = document.getElementById("budgetWarning_<?php echo $row['project_id']; ?>");
        if (budgetInput) {
            budgetInput.addEventListener("input", function() {
                const value = parseFloat(budgetInput.value.replace(/,/g, "")) || 0;
                if (value < 1000) {
                    budgetWarning.style.display = "block";
                    budgetInput.classList.add("is-invalid");
                    budgetInput.setCustomValidity("Project Cost must be at least 1000");
                } else {
                    budgetWarning.style.display = "none";
                    budgetInput.classList.remove("is-invalid");
                    budgetInput.setCustomValidity("");
                }
            });
        }
    });


    // // Load Provinces, Cities, and Barangays
    // document.addEventListener("DOMContentLoaded", function() {
    //     document.querySelectorAll('[data-province]').forEach(function(provinceSelect) {
    //         const modal = provinceSelect.closest('.modal');
    //         const citySelect = modal.querySelector('[data-city]');
    //         const barangaySelect = modal.querySelector('[data-barangay]');

    //         const selectedProvince = provinceSelect.getAttribute('data-selected');
    //         const selectedCity = citySelect.getAttribute('data-selected');
    //         const selectedBarangay = barangaySelect.getAttribute('data-selected');

    //         // Load Provinces
    //         fetch("https://psgc.gitlab.io/api/provinces/")
    //             .then(res => res.json())
    //             .then(data => {
    //                 data.sort((a, b) => a.name.localeCompare(b.name)); // Sort alphabetically
    //                 data.forEach(province => {
    //                     const option = document.createElement("option");
    //                     option.value = province.name;
    //                     option.text = province.name;
    //                     if (province.name === selectedProvince) option.selected = true;
    //                     provinceSelect.appendChild(option);
    //                 });

    //                 // Trigger city load if province matches
    //                 if (selectedProvince) {
    //                     loadCitiesByProvinceName(selectedProvince);
    //                 }
    //             });

    //         // When province changes
    //         provinceSelect.addEventListener("change", function() {
    //             loadCitiesByProvinceName(this.value);
    //         });

    //         function loadCitiesByProvinceName(provinceName) {
    //             fetch("https://psgc.gitlab.io/api/provinces/")
    //                 .then(res => res.json())
    //                 .then(provinces => {
    //                     const match = provinces.find(p => p.name === provinceName);
    //                     if (!match) return;

    //                     fetch(`https://psgc.gitlab.io/api/provinces/${match.code}/cities-municipalities/`)
    //                         .then(res => res.json())
    //                         .then(cities => {
    //                             citySelect.innerHTML = '<option value="" disabled hidden>Select City/Municipality</option>';
    //                             barangaySelect.innerHTML = '<option value="" disabled hidden>Select Barangay</option>';

    //                             cities.sort((a, b) => a.name.localeCompare(b.name)); // Sort alphabetically
    //                             cities.forEach(city => {
    //                                 const option = document.createElement("option");
    //                                 option.value = city.name;
    //                                 option.text = city.name;
    //                                 if (city.name === selectedCity) option.selected = true;
    //                                 citySelect.appendChild(option);
    //                             });

    //                             if (selectedCity) {
    //                                 loadBarangaysByCityName(selectedCity);
    //                             }
    //                         });
    //                 });
    //         }

    //         // When city changes
    //         citySelect.addEventListener("change", function() {
    //             loadBarangaysByCityName(this.value);
    //         });

    //         function loadBarangaysByCityName(cityName) {
    //             fetch("https://psgc.gitlab.io/api/cities-municipalities/")
    //                 .then(res => res.json())
    //                 .then(cities => {
    //                     const match = cities.find(c => c.name === cityName);
    //                     if (!match) return;

    //                     fetch(`https://psgc.gitlab.io/api/cities-municipalities/${match.code}/barangays/`)
    //                         .then(res => res.json())
    //                         .then(barangays => {
    //                             barangaySelect.innerHTML = '<option value="" disabled hidden>Select Barangay</option>';
    //                             barangays.sort((a, b) => a.name.localeCompare(b.name)); // Sort alphabetically
    //                             barangays.forEach(brgy => {
    //                                 const option = document.createElement("option");
    //                                 option.value = brgy.name;
    //                                 option.text = brgy.name;
    //                                 if (brgy.name === selectedBarangay) option.selected = true;
    //                                 barangaySelect.appendChild(option);
    //                             });
    //                         });
    //                 });
    //         }
    //     });
    // });

    // // Date Picker Initialization
    // document.addEventListener("DOMContentLoaded", function() {
    //     document.querySelectorAll('.modal').forEach(modal => {
    //         modal.addEventListener('shown.bs.modal', function() {
    //             const startInput = modal.querySelector(".start-date");
    //             const endInput = modal.querySelector(".end-date");

    //             if (startInput && endInput) {
    //                 const startPicker = flatpickr(startInput, {
    //                     dateFormat: "Y-m-d",
    //                     minDate: "today",
    //                     maxDate: new Date().fp_incr(180), // 6 months ahead
    //                     disableMobile: true,
    //                     onChange: function(selectedDates) {
    //                         if (selectedDates.length > 0) {
    //                             const selectedStartDate = selectedDates[0];
    //                             endPicker.set("minDate", selectedStartDate); // Update the minDate of the end date picker
    //                         }
    //                     },
    //                 });

    //                 const endPicker = flatpickr(endInput, {
    //                     dateFormat: "Y-m-d",
    //                     minDate: "today",
    //                     maxDate: new Date().fp_incr(365), // 1 year ahead
    //                     disableMobile: true,
    //                 });
    //             }
    //         });
    //     });
    // });

    // // Budget Input Formatting and Validation
    // document.addEventListener("DOMContentLoaded", function() {
    //     document.querySelectorAll('.modal').forEach(modal => {
    //         modal.addEventListener('shown.bs.modal', function() {
    //             const budgetInput = modal.querySelector(`[id^="budgetInput_"]`);
    //             const budgetWarning = modal.querySelector(`[id^="budgetWarning_"]`);

    //             if (budgetInput && budgetWarning) {
    //                 budgetInput.addEventListener("input", function() {
    //                     let value = budgetInput.value.replace(/[^0-9.]/g, "");
    //                     const parts = value.split(".");
    //                     parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    //                     budgetInput.value = parts.join(".");

    //                     const numericValue = parseFloat(value.replace(/,/g, ""));
    //                     if (numericValue < 10000) {
    //                         budgetWarning.style.display = "block";
    //                         budgetInput.classList.add("is-invalid");
    //                     } else {
    //                         budgetWarning.style.display = "none";
    //                         budgetInput.classList.remove("is-invalid");
    //                     }
    //                 });
    //             }
    //         });
    //     });
    // });
</script>