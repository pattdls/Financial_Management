<?php
// Fetch 3 contact persons for the client
$contacts = [];
$contact_query = "SELECT * FROM contacts WHERE client_id = '$client_id' LIMIT 3";
$contact_result = mysqli_query($connection, $contact_query);
if ($contact_result) {
    while ($row = mysqli_fetch_assoc($contact_result)) {
        $contacts[] = $row;
    }
}
// Ensure 3 contacts for form display (fill empty if less than 3)
while (count($contacts) < 3) {
    $contacts[] = ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix_name' => '', 'email' => '', 'phone_number' => '', 'designation' => ''];
}
?>

<div class="modal fade" id="editClientModal<?php echo $client_id; ?>" tabindex="-1" aria-labelledby="editClientLabel<?php echo $client_id; ?>" aria-hidden="true">
    <div class="modal-dialog edit-company-modal"> 
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title " id="editClientLabel">Edit Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body edit-client-body">
                <form action="../forms_logic/update_client.php" method="POST">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

                    <!-- Company Name -->
                    <p class="mb-2">Client Name/ Company Name<span style="color: red;">*</span></p>
                    <div class="mb-4">
                        <input type="text" class="form-control" name="client_name" value="<?php echo htmlspecialchars($client_row['client_name']); ?>" required>
                        <label class="text-muted form-label">Company Name</label>
                    </div>
                    
                    <!-- Loop through 3 contact persons -->
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <p class="mb-2 mt-3">Contact Person <?php echo chr(65 + $i); ?> <span style="color: red;">*</span></p>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="first_name[]" value="<?php echo htmlspecialchars($contacts[$i]['first_name']); ?>" required>
                                <label for="first-name" class="text-muted form-label">First Name</label>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="middle_name[]" value="<?php echo htmlspecialchars($contacts[$i]['middle_name']); ?>">
                                <label for="middle-initial" class="text-muted form-label">Middle Name</label>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="last_name[]" value="<?php echo htmlspecialchars($contacts[$i]['last_name']); ?>" required>
                                <label for="last-name" class="text-muted form-label">Last Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="suffix_name[]" value="<?php echo htmlspecialchars($contacts[$i]['suffix_name']); ?>">
                                <label for="suffix_name" class="text-muted form-label">Suffix <i class="ms-2" style="color: #8a8a8a;">ex. Jr., Sr., III.</i></label>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <input type="email" 
                                    class="text-muted form-control"
                                    name="email[]"
                                    value="<?php echo htmlspecialchars($contacts[$i]['email']); ?>"
                                    placeholder="ex: email@yahoo.com"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                                <label for="contact-email" class="text-muted form-label">Email<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-4">
                               
                                <input type="text" class="text-muted form-control" name="phone_number[]" value="<?php echo htmlspecialchars($contacts[$i]['phone_number']); ?>"
                                    placeholder="e.g. 09XXXXXXXXX"
                                    pattern="^09\d{9}$"
                                    maxlength="11"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid phone number.
                                </div>
                                <label for="contact-number" class="text-muted form-label">Phone Number<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-4">
                            <select class="form-control" name="designation[]">
                                <option value="" disabled <?php echo empty($contacts[$i]['designation']) ? 'selected' : ''; ?>>Select Designation</option>
                                <option value="Engineering" <?php echo ($contacts[$i]['designation'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                <option value="Purchasing" <?php echo ($contacts[$i]['designation'] == 'Purchasing') ? 'selected' : ''; ?>>Purchasing</option>
                                <option value="Accounting" <?php echo ($contacts[$i]['designation'] == 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                            </select>
                            <label for="designation" class="text-muted form-label">Designation<span style="color: red;">*</span></label>
                        </div>
                        </div>

                        
                    <?php endfor; ?>

                    

                    <!-- Address -->
                    <p class="mb-2">Address<span style="color: red;">*</span></p>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <select class="form-control" id="province_<?php echo $client_id; ?>" name="province" required>
                                <option value="<?php echo $client_row['province']; ?>"><?php echo $client_row['province']; ?></option>
                            </select>
                            <label class="text-muted form-label">Province</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control" id="city_<?php echo $client_id; ?>" name="city" required>
                                <option value="<?php echo $client_row['city']; ?>"><?php echo $client_row['city']; ?></option>
                            </select>
                            <label class="text-muted form-label select2">City/Municipality</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control" id="barangay_<?php echo $client_id; ?>" name="barangay" required>
                                <option value="<?php echo $client_row['barangay']; ?>"><?php echo $client_row['barangay']; ?></option>
                            </select>
                            <label class="text-muted form-label select2">Barangay</label>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="street_address" value="<?php echo $client_row['street_address']; ?>" required>
                            <label for="address" class="text-muted form-label">Street Address/House Number/Building Number</label>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="zip_code" value="<?php echo $client_row['zip_code']; ?>" required>
                            <label for="address" class="text-muted form-label">ZIP code</label>
                        </div>
                    </div>
                     <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success ms-2" name="save_changes">Save Changes</button>
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