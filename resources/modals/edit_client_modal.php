<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Using extra large modal for wide form -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClientLabel">Edit Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../forms_logic/code.php" method="POST">
                    <div class="">
                        <!-- Company Name -->
                        <p class="mb-2 mt-3">Client Name/ Company Name <span style="color: red;">*</span></p>
                        <div class="mb-3">
                            <input type="text" class="form-control hover-effect" name="client_name" required>
                            <label class="text-muted form-label">Company Name</label>
                        </div>
                        <!-- Contact Person A-->
                        <p class="mb-2 mt-3">Contact Person A </p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" required>
                                <label for="first-name" class="text-muted form-label">First Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                <label for="middle-initial" class="text-muted form-label">M.I</label>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" required>
                                <label for="last-name" class="text-muted form-label">Last Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                <label for="suffix_name" class="text-muted form-label">Suffix</label>
                            </div>

                            <!-- Email and Phone -->
                            <div class="col-md-4">
                                <input type="email"
                                    class="form-control text-muted hover-effect"
                                    name="email[]"
                                    placeholder="ex: email@yahoo.com"
                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                                <label for="email" class="text-muted from-label">Email <span style="color: red;">*</span></label>
                            </div>

                            <div class="col-md-4">
                                <input type="tel" name="client_phone_number[]" class="form-control text-muted hover-effect"
                                    placeholder="e.g. 09XXXXXXXXX"
                                    pattern="^09\d{9}$"
                                    maxlength="11"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid phone number.
                                </div>
                                <label for="phone_number" class="text-muted from-label">Phone Number <span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control hover-effect" name="designation[]">
                                    <option value="" disabled selected hidden>Select Designation</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Purchasing">Purchasing</option>
                                    <option value="Accounting">Accounting</option>
                                </select>
                                <label for="designation" class="text-muted form-label">Designation<span style="color: red;">*</span></label>
                            </div>
                        </div>
                        <!-- Contact Person B-->
                        <p class="mb-2 mt-3">Contact Person B </p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" required>
                                <label for="first-name" class="text-muted form-label">First Name<span style="color: red;">*</span></label>
                            </div>

                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                <label for="middle-initial" class="text-muted form-label">M.I</label>
                            </div>

                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" required>
                                <label for="last-name" class="text-muted form-label">Last Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                <label for="suffix_name" class="text-muted form-label">Suffix</label>
                            </div>

                            <!-- Email and Phone -->
                            <div class="col-md-4">
                                <input type="email"
                                    class="form-control text-muted hover-effect"
                                    name="email[]"
                                    placeholder="ex: email@yahoo.com"
                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                                <label for="email" class="text-muted from-label">Email <span style="color: red;">*</span></label>
                            </div>

                            <div class="col-md-4">
                                <input type="tel" name="client_phone_number[]" class="form-control text-muted hover-effect"
                                    placeholder="e.g. 09XXXXXXXXX"
                                    pattern="^09\d{9}$"
                                    maxlength="11"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid phone number.
                                </div>
                                <label for="phone_number" class="text-muted from-label">Phone Number <span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control hover-effect" name="designation[]">
                                    <option value="" disabled selected hidden>Select Designation</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Purchasing">Purchasing</option>
                                    <option value="Accounting">Accounting</option>
                                </select>
                                <label for="designation" class="text-muted form-label">Designation<span style="color: red;">*</span></label>
                            </div>
                        </div>
                        <!-- Contact Person C-->
                        <p class="mb-2 mt-3">Contact Person C </p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="first_name[]" required>
                                <label for="first-name" class="text-muted form-label">First Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="middle_name[]" maxlength="2" style="text-transform: uppercase;">
                                <label for="middle-initial" class="text-muted form-label">M.I</label>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control hover-effect name-capitalize" name="last_name[]" required>
                                <label for="last-name" class="text-muted form-label">Last Name<span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="suffix_name[]">
                                <label for="suffix_name" class="text-muted form-label">Suffix</label>
                            </div>

                            <!-- Email and Phone -->
                            <div class="col-md-4">
                                <input type="email"
                                    class="form-control text-muted hover-effect"
                                    name="email[]"
                                    placeholder="ex: email@yahoo.com"
                                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[cC][oO][mM]$"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                                <label for="email" class="text-muted from-label">Email <span style="color: red;">*</span></label>
                            </div>

                            <div class="col-md-4">
                                <input type="tel" name="client_phone_number[]" class="form-control text-muted hover-effect"
                                    placeholder="e.g. 09XXXXXXXXX"
                                    pattern="^09\d{9}$"
                                    maxlength="11"
                                    required>
                                <div class="invalid-feedback">
                                    Please enter a valid phone number.
                                </div>
                                <label for="phone_number" class="text-muted from-label">Phone Number <span style="color: red;">*</span></label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control hover-effect" name="designation[]">
                                    <option value="" disabled selected hidden>Select Designation</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Purchasing">Purchasing</option>
                                    <option value="Accounting">Accounting</option>
                                </select>
                                <label for="designation" class="text-muted form-label">Designation<span style="color: red;">*</span></label>
                            </div>
                        </div>

                        <p class="mb-2 mt-3">Address <span style="color: red;">*</span></p>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <select class="form-control select2 hover-effect" id="province" name="province" required>
                                    <option value="" disabled selected hidden>Select Province</option>
                                </select>
                                <label class="text-muted form-label">Province</label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control hover-effect" id="city" name="city" required>
                                    <option disabled selected hidden>Select City/Municipality</option>
                                </select>
                                <label class="text-muted form-label">City/Municipality</label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control hover-effect" id="barangay" name="barangay" required>
                                    <option disabled selected hidden>Select Barangay</option>
                                </select>
                                <label class="text-muted form-label">Barangay</label>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control hover-effect" name="street_address" required>
                                <label for="address" class="text-muted form-label">Street Address / House / Building No.</label>
                            </div>
                            <div class="col-md-2">
                                <input type="text" class="form-control hover-effect" name="zip_code" required>
                                <label for="address" class="text-muted form-label">ZIP Code</label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="save_data" class="btn btn-success">Save Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
