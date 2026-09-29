<!-- View Details Modal -->
<div class="modal fade" id="viewClientModal<?php echo $client_id; ?>" tabindex="-1" aria-labelledby="viewClientLabel<?php echo $client_id; ?>" aria-hidden="true">
    <div class="modal-dialog view-client-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewClientLabel<?php echo $client_id; ?>">Client Details</h5>
                <div class="client-type-badge ms-3">
                    <?php if (strtolower($client_row['client_type']) === 'individual'): ?>
                        <span class="badge bg-primary rounded-pill">
                            <i class="bi bi-person-fill me-1"></i>Individual
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success rounded-pill">
                            <i class="bi bi-building-fill me-1"></i>Company
                        </span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Client Name -->
                <div class="d-flex flex-column">
                    <span class="view-client-name"><?php echo htmlspecialchars($client_row['client_name']); ?></span>
                    <small class="text-muted">Client Name</small>
                </div>
                <!-- Address -->
                <div class="d-flex flex-column address-client-details">
                    <span><?php echo htmlspecialchars($client_row['street_address'] . ', ' . $client_row['barangay'] . ', ' . $client_row['city'] . ', ' . $client_row['province'] . ' ' . $client_row['zip_code']); ?></span>
                    <small class="text-muted">Address</small>
                </div>
                <!-- Phone and Email -->
                <?php if (strtolower($client_row['client_type']) === 'individual'): ?>
                    <div class="client-details-content">
                        <div class="col-md-4 ">
                            <div class="d-flex flex-column phone-num-details">
                                <span><?php echo htmlspecialchars($client_row['phone_number']); ?></span>
                                <small class="text-muted ">Phone Number</small>
                            </div>
                            <div class="d-flex flex-column email-details">
                                <span class=""><?php echo htmlspecialchars($client_row['email']); ?></span>
                                <small class="text-muted ">Email</small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <hr>
                    <div class="company-contact-persons">Contact Persons</div>
                    <?php
                    if (!empty($contact_details) && is_array($contact_details)) {
                        echo '<div class="row">';
                        static $contactCounter = 0;

                        foreach ($contact_details as $contact) {
                            if (is_array($contact)) {
                                $contactLabel = 'Contact Person ' . chr(65 + $contactCounter++);
                                echo '<div class="col-12 company-client-details mb-3">';
                                echo "<div class='d-block'>" . htmlspecialchars($contact['name']) . "</div>";
                                echo "<small class='text-muted d-block'>"
                                    . htmlspecialchars($contact['designation']) . " | "
                                    . htmlspecialchars($contact['phone']) . " | "
                                    . htmlspecialchars($contact['email'])
                                    . "</small>";

                                echo '</div>';
                            } else {
                                echo '<div class="col-12 mb-3">';
                                echo htmlspecialchars($contact) . "<br>";
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                    } else {
                        echo "<p>No Contact Persons Found.</p>";
                    }
                    ?>
                <?php endif; ?>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top">
                <div class="d-flex justify-content-end w-100 align-items-end">
                    <!--<small class="text-muted">-->
                    <!--    <i class="bi bi-calendar-check me-1"></i>-->
                    <!--    Client since <?php echo date('M Y', strtotime($client_row['created_at'] ?? 'now')); ?>-->
                    <!--</small>-->
                    <div>
                        <a href="projects.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm me-2">
                            <i class="bi bi-folder2-open me-1"></i>View Projects
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of View Details Modal -->