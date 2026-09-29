<?php
$client_id = $client_row['client_id'];
?>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal<?php echo $client_id; ?>" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClientModalLabel">Edit Individual Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="individualForm" action="../forms_logic/update_client.php" method="POST">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <input type="hidden" name="client_type" value="Individual">
                    <!-- Client Name -->
                    <p class="mb-2 mt-3">
                        <?php echo htmlspecialchars($client_row['client_name']); ?>
                        <span style="color: red;">*</span>
                    </p>
                    <div class="">
                        <input type="text" class="form-control hover-effect" name="client_name" required
                            value="<?php echo htmlspecialchars($client_row['client_name']); ?>">
                        <label class="text-muted form-label">Full Name</label>
                    </div>
                    

                    <!-- Email and Phone -->
                    <div class="row">
                        <p class="mb-2 mt-3">Email and Phone Number <span style="color: red;">*</span></p>
                        <div class="col-md-6">
                            <input type="email" class="form-control text-muted hover-effect" name="email" id="email_<?php echo $client_id; ?>"
                                placeholder="ex: email@yahoo.com" pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$" required
                                value="<?php echo htmlspecialchars($client_row['email']); ?>">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                            <label class="text-muted form-label">Email</label>
                        </div>
                        <div class="col-md-6">
                            <input type="tel" name="phone_number" class="form-control text-muted hover-effect"
                                placeholder="e.g. 09XXXXXXXXX" pattern="^09\d{9}$" maxlength="11" required
                                value="<?php echo htmlspecialchars($client_row['phone_number']); ?>">
                            <div class="invalid-feedback">Please enter a valid phone number.</div>
                            <label class="text-muted form-label">Phone Number</label>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <p class="mb-2 mt-3">Address <span style="color: red;">*</span></p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <select class="form-control select2 hover-effect" id="province_<?php echo $client_id; ?>" name="province" required>
                                <option value="" disabled hidden>Select Province</option>
                                <option value="<?php echo htmlspecialchars($client_row['province']); ?>" selected>
                                    <?php echo htmlspecialchars($client_row['province']); ?>
                                </option>
                                <!-- Add other options -->
                            </select>
                            <label class="text-muted form-label">Province</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control hover-effect" id="city_<?php echo $client_id; ?>" name="city" required>
                                <option disabled hidden>Select City/Municipality</option>
                                <option value="<?php echo htmlspecialchars($client_row['city']); ?>" selected>
                                    <?php echo htmlspecialchars($client_row['city']); ?>
                                </option>
                                <!-- Add other options -->
                            </select>
                            <label class="text-muted form-label">City/Municipality</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control hover-effect" id="barangay_<?php echo $client_id; ?>" name="barangay" required>
                                <option disabled hidden>Select Barangay</option>
                                <option value="<?php echo htmlspecialchars($client_row['barangay']); ?>" selected>
                                    <?php echo htmlspecialchars($client_row['barangay']); ?>
                                </option>
                                <!-- Add other options -->
                            </select>
                            <label class="text-muted form-label">Barangay</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control hover-effect" name="street_address" required
                                value="<?php echo htmlspecialchars($client_row['street_address']); ?>">
                            <label class="text-muted form-label">Street Address / House / Building No.</label>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control hover-effect" name="zip_code" required
                                value="<?php echo htmlspecialchars($client_row['zip_code']); ?>">
                            <label class="text-muted form-label">ZIP Code</label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_changes" class="btn btn-success ms-2">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("#editClientModal<?php echo $client_id; ?> form");
        const emailInput = document.getElementById("email_<?php echo $client_id; ?>");

        form.addEventListener("submit", function(event) {
            const emailValue = emailInput.value.trim();

            // Regex: full email validation + must end with .com
            const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com)$/i;

            if (!regex.test(emailValue)) {
                emailInput.classList.add("is-invalid");
                event.preventDefault();
                event.stopPropagation();
            } else {
                emailInput.classList.remove("is-invalid");
            }

            // Standard Bootstrap validation
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add("was-validated");
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const provinceSelect = document.getElementById("province_<?php echo $client_id; ?>");
        const citySelect = document.getElementById("city_<?php echo $client_id; ?>");
        const barangaySelect = document.getElementById("barangay_<?php echo $client_id; ?>");

        const selectedProvince = "<?php echo $client_row['province']; ?>";
        const selectedCity = "<?php echo $client_row['city']; ?>";
        const selectedBarangay = "<?php echo $client_row['barangay']; ?>";

        function clearSelect(selectElement, placeholderText) {
            selectElement.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;
        }

        // Load Provinces
        fetch("https://psgc.gitlab.io/api/provinces/")
            .then(res => res.json())
            .then(data => {
                data.sort((a, b) => a.name.localeCompare(b.name));
                clearSelect(provinceSelect, "-- Select Province --"); // Add default option first
                data.forEach(province => {
                    const option = document.createElement("option");
                    option.value = province.name;
                    option.text = province.name;
                    if (province.name === selectedProvince) option.selected = true;
                    provinceSelect.appendChild(option);
                });

                // Trigger city load if province matches
                if (selectedProvince) {
                    loadCitiesByProvinceName(selectedProvince);
                }
            });

        provinceSelect.addEventListener("change", function() {
            clearSelect(citySelect, "-- Select City/Municipality --");
            clearSelect(barangaySelect, "-- Select Barangay --");
            loadCitiesByProvinceName(this.value);
        });

        function loadCitiesByProvinceName(provinceName) {
            fetch("https://psgc.gitlab.io/api/provinces/")
                .then(res => res.json())
                .then(provinces => {
                    const match = provinces.find(p => p.name === provinceName);
                    if (!match) return;

                    fetch(`https://psgc.gitlab.io/api/provinces/${match.code}/cities-municipalities/`)
                        .then(res => res.json())
                        .then(cities => {
                            clearSelect(citySelect, "-- Select City/Municipality --");
                            clearSelect(barangaySelect, "-- Select Barangay --");

                            cities.sort((a, b) => a.name.localeCompare(b.name));
                            cities.forEach(city => {
                                const option = document.createElement("option");
                                option.value = city.name;
                                option.text = city.name;
                                if (city.name === selectedCity) option.selected = true;
                                citySelect.appendChild(option);
                            });

                            // Auto-load barangays if still matching old selected city
                            if (selectedCity && provinceName === selectedProvince) {
                                loadBarangaysByCityName(selectedCity);
                            }
                        });
                });
        }

        citySelect.addEventListener("change", function() {
            clearSelect(barangaySelect, "-- Select Barangay --");
            loadBarangaysByCityName(this.value);
        });

        function loadBarangaysByCityName(cityName) {
            fetch("https://psgc.gitlab.io/api/cities-municipalities/")
                .then(res => res.json())
                .then(cities => {
                    const match = cities.find(c => c.name === cityName);
                    if (!match) return;

                    fetch(`https://psgc.gitlab.io/api/cities-municipalities/${match.code}/barangays/`)
                        .then(res => res.json())
                        .then(barangays => {
                            clearSelect(barangaySelect, "-- Select Barangay --");
                            barangays.sort((a, b) => a.name.localeCompare(b.name));
                            barangays.forEach(brgy => {
                                const option = document.createElement("option");
                                option.value = brgy.name;
                                option.text = brgy.name;
                                if (brgy.name === selectedBarangay) option.selected = true;
                                barangaySelect.appendChild(option);
                            });
                        });
                });
        }
    });
</script>