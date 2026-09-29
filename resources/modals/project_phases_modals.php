<?php
// Fetch phase logs
$phaseLogs = [];
$phaseLogsByPhase = [];
$logs_query = mysqli_query($conn, "SELECT phase_name, status, updated_at FROM project_phase_logs WHERE project_id = '$project_id' ORDER BY updated_at DESC");
while ($log = mysqli_fetch_assoc($logs_query)) {
    $phaseLogs[] = $log;
    $phaseLogsByPhase[$log['phase_name']][] = $log;
}
?>

<!-- Design & Permits Phase Log Modal -->
<div class="modal fade" id="logModalDesignPermits" tabindex="-1" aria-labelledby="logModalDesignPermitsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalDesignPermitsLabel">Design & Permits Updates</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Latest Updates:</h6>
                <ul class="list-unstyled fs-6">
                    <?php
                    $phaseName = 'Design & Permits';
                    if (!empty($phaseLogsByPhase[$phaseName])):
                        foreach ($phaseLogsByPhase[$phaseName] as $log):
                            $date = date('M j, Y', strtotime($log['updated_at']));
                            $status = $log['status'];
                            if ($status === 'Pending') {
                                $msg = "Set to <strong>Pending</strong> on $date.";
                            } elseif ($status === 'In Progress') {
                                $msg = "In progress starting from $date.";
                            } elseif ($status === 'Completed') {
                                $msg = "Completed on $date.";
                            } else {
                                $msg = "A progress update has been sent to client on $date.";
                            }
                            echo "<li>- $msg</li>";
                        endforeach;
                    else:
                        echo "<li class='text-muted'>No updates yet.</li>";
                    endif;
                    ?>
                </ul>
                <hr class="my-4">

                <!-- Add New Update Section -->
                <div class="update-section">
                    <h6 class="mb-3 text-dark fw-bold">
                        <i class="fas fa-plus-circle text-primary"></i>Add Progress Update
                    </h6>
                    <p class="text-muted fs-6 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Updates will be automatically sent as reports to the user's dashboard and email notifications.
                    </p>

                    <form id="phaseUpdateForm" enctype="multipart/form-data">
                        <input type="hidden" name="phase_name" value="Design & Permits">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                        <!-- Progress Description -->
                        <div class="mb-3">
                            <label for="updateDescription" class="form-label fw-semibold">
                                <i class="fas fa-edit me-1"></i>Progress Description
                            </label>
                            <textarea class="form-control" id="updateDescription" name="description"
                                rows="4" placeholder="Describe the current progress, challenges, or milestones achieved..." required></textarea>
                            <div class="form-text fs-6">
                                <i class="fas fa-lightbulb me-1"></i>
                                Be specific about what's been accomplished and any next steps.
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-3">
                            <label for="progressImages" class="form-label fw-semibold">
                                <i class="fas fa-camera me-1"></i>Progress Photos & Documents
                            </label>
                            <input type="file" class="form-control" id="progressImages" name="progress_files[]"
                                multiple accept="image/*,.pdf,.doc,.docx,.dwg">
                            <div class="form-text">
                                <i class="fas fa-upload me-1"></i>
                                Upload photos, plans, permits, or documents
                            </div>
                            <div id="filePreview" class="mt-2"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Update & Notify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Design & Permits Phase Log Modal -->

<!-- Material Procurement Phase Log Modal -->
<div class="modal fade" id="logModalMaterialProcurement" tabindex="-1" aria-labelledby="logModalMaterialProcurementLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content ">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalMaterialProcurementLabel">Material Procurement Updates</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Latest Updates:</h6>
                <ul class="list-unstyled fs-6">
                    <?php
                    $phaseName = 'Material Procurement';
                    if (!empty($phaseLogsByPhase[$phaseName])):
                        foreach ($phaseLogsByPhase[$phaseName] as $log):
                            $date = date('M j, Y', strtotime($log['updated_at']));
                            $status = $log['status'];
                            if ($status === 'Pending') {
                                $msg = "Set to <strong>Pending</strong> on $date.";
                            } elseif ($status === 'In Progress') {
                                $msg = "In progress starting from $date.";
                            } elseif ($status === 'Completed') {
                                $msg = "Completed on $date.";
                            } else {
                                $msg = "A <strong>$status</strong> has been sent to client on $date.";
                            }
                            echo "<li>- $msg</li>";
                        endforeach;
                    else:
                        echo "<li class='text-muted'>No updates yet.</li>";
                    endif;
                    ?>
                </ul>
                <hr class="my-4">

                <!-- Add New Update Section -->
                <div class="update-section">
                    <h6 class="mb-3 text-dark fw-bold">
                        <i class="fas fa-plus-circle text-primary"></i>Add Progress Update
                    </h6>
                    <p class="text-muted fs-6 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Updates will be automatically sent as reports to the user's dashboard and email notifications.
                    </p>

                    <form id="phaseUpdateForm" enctype="multipart/form-data">
                        <input type="hidden" name="phase_name" value="Material Procurement">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                        <!-- Progress Description -->
                        <div class="mb-3">
                            <label for="updateDescription" class="form-label fw-semibold">
                                <i class="fas fa-edit me-1"></i>Progress Description
                            </label>
                            <textarea class="form-control" id="updateDescription" name="description"
                                rows="4" placeholder="Describe the current progress, challenges, or milestones achieved..." required></textarea>
                            <div class="form-text fs-6">
                                <i class="fas fa-lightbulb me-1"></i>
                                Be specific about what's been accomplished and any next steps.
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-3">
                            <label for="progressImages" class="form-label fw-semibold">
                                <i class="fas fa-camera me-1"></i>Progress Photos & Documents
                            </label>
                            <input type="file" class="form-control" id="progressImages" name="progress_files[]"
                                multiple accept="image/*,.pdf,.doc,.docx,.dwg">
                            <div class="form-text">
                                <i class="fas fa-upload me-1"></i>
                                Upload photos, plans, permits, or documents
                            </div>
                            <div id="filePreview" class="mt-2"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Update & Notify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Material Procurement Phase Log Modal -->

<!-- Site Preparation Phase Log Modal -->
<div class="modal fade" id="logModalSitePreparation" tabindex="-1" aria-labelledby="logModalSitePreparationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalSitePreparationLabel">Site Preparation Updates</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Latest Updates:</h6>
                <ul class="list-unstyled fs-6">
                    <?php
                    $phaseName = 'Site Preparation';
                    if (!empty($phaseLogsByPhase[$phaseName])):
                        foreach ($phaseLogsByPhase[$phaseName] as $log):
                            $date = date('M j, Y', strtotime($log['updated_at']));
                            $status = $log['status'];
                            if ($status === 'Pending') {
                                $msg = "Set to <strong>Pending</strong> on $date.";
                            } elseif ($status === 'In Progress') {
                                $msg = "In progress starting from $date.";
                            } elseif ($status === 'Completed') {
                                $msg = "Completed on $date.";
                            } else {
                                $msg = "A <strong>$status</strong> has been sent to client on $date.";
                            }
                            echo "<li>- $msg</li>";
                        endforeach;
                    else:
                        echo "<li class='text-muted'>No updates yet.</li>";
                    endif;
                    ?>
                </ul>
                <hr class="my-4">

                <!-- Add New Update Section -->
                <div class="update-section">
                    <h6 class="mb-3 text-dark fw-bold">
                        <i class="fas fa-plus-circle text-primary"></i>Add Progress Update
                    </h6>
                    <p class="text-muted fs-6 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Updates will be automatically sent as reports to the user's dashboard and email notifications.
                    </p>

                    <form id="phaseUpdateForm" enctype="multipart/form-data">
                        <input type="hidden" name="phase_name" value="Site Preparation">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                        <!-- Progress Description -->
                        <div class="mb-3">
                            <label for="updateDescription" class="form-label fw-semibold">
                                <i class="fas fa-edit me-1"></i>Progress Description
                            </label>
                            <textarea class="form-control" id="updateDescription" name="description"
                                rows="4" placeholder="Describe the current progress, challenges, or milestones achieved..." required></textarea>
                            <div class="form-text fs-6">
                                <i class="fas fa-lightbulb me-1"></i>
                                Be specific about what's been accomplished and any next steps.
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-3">
                            <label for="progressImages" class="form-label fw-semibold">
                                <i class="fas fa-camera me-1"></i>Progress Photos & Documents
                            </label>
                            <input type="file" class="form-control" id="progressImages" name="progress_files[]"
                                multiple accept="image/*,.pdf,.doc,.docx,.dwg">
                            <div class="form-text">
                                <i class="fas fa-upload me-1"></i>
                                Upload photos, plans, permits, or documents
                            </div>
                            <div id="filePreview" class="mt-2"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Update & Notify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Site Preparation Phase Log Modal -->

<!-- Project Installation Phase Log Modal -->
<div class="modal fade" id="logModalProjectInstallation" tabindex="-1" aria-labelledby="logModalProjectInstallation" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalProjectInstallation">Project Installation Updates</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Latest Updates:</h6>
                <ul class="list-unstyled fs-6">
                    <?php
                    $phaseName = 'Project Installation';
                    if (!empty($phaseLogsByPhase[$phaseName])):
                        foreach ($phaseLogsByPhase[$phaseName] as $log):
                            $date = date('M j, Y', strtotime($log['updated_at']));
                            $status = $log['status'];
                            if ($status === 'Pending') {
                                $msg = "Set to <strong>Pending</strong> on $date.";
                            } elseif ($status === 'In Progress') {
                                $msg = "In progress starting from $date.";
                            } elseif ($status === 'Completed') {
                                $msg = "Completed on $date.";
                            } else {
                                $msg = "Status changed to <strong>$status</strong> on $date.";
                            }
                            echo "<li>- $msg</li>";
                        endforeach;
                    else:
                        echo "<li class='text-muted'>No updates yet.</li>";
                    endif;
                    ?>
                </ul>
                <hr class="my-4">

                <!-- Add New Update Section -->
                <div class="update-section">
                    <h6 class="mb-3 text-dark fw-bold">
                        <i class="fas fa-plus-circle text-primary"></i>Add Progress Update
                    </h6>
                    <p class="text-muted fs-6 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Updates will be automatically sent as reports to the user's dashboard and email notifications.
                    </p>

                    <form id="phaseUpdateForm" enctype="multipart/form-data">
                        <input type="hidden" name="phase_name" value="Project Installation">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                        <!-- Progress Description -->
                        <div class="mb-3">
                            <label for="updateDescription" class="form-label fw-semibold">
                                <i class="fas fa-edit me-1"></i>Progress Description
                            </label>
                            <textarea class="form-control" id="updateDescription" name="description"
                                rows="4" placeholder="Describe the current progress, challenges, or milestones achieved..." required></textarea>
                            <div class="form-text fs-6">
                                <i class="fas fa-lightbulb me-1"></i>
                                Be specific about what's been accomplished and any next steps.
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-3">
                            <label for="progressImages" class="form-label fw-semibold">
                                <i class="fas fa-camera me-1"></i>Progress Photos & Documents
                            </label>
                            <input type="file" class="form-control" id="progressImages" name="progress_files[]"
                                multiple accept="image/*,.pdf,.doc,.docx,.dwg">
                            <div class="form-text">
                                <i class="fas fa-upload me-1"></i>
                                Upload photos, plans, permits, or documents
                            </div>
                            <div id="filePreview" class="mt-2"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Update & Notify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Project Installation Phase Log Modal -->

<!-- Final Inspection Phase Log Modal -->
<div class="modal fade" id="logModalFinalInspection" tabindex="-1" aria-labelledby="logModalFinalInspectionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalFinalInspectionLabel">Final Inspection Updates</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Latest Updates:</h6>
                <ul class="list-unstyled fs-6">
                    <?php
                    $phaseName = 'Final Inspection';
                    if (!empty($phaseLogsByPhase[$phaseName])):
                        foreach ($phaseLogsByPhase[$phaseName] as $log):
                            $date = date('M j, Y', strtotime($log['updated_at']));
                            $status = $log['status'];
                            if ($status === 'Pending') {
                                $msg = "Set to <strong>Pending</strong> on $date.";
                            } elseif ($status === 'In Progress') {
                                $msg = "In progress starting from $date.";
                            } elseif ($status === 'Completed') {
                                $msg = "Completed on $date.";
                            } else {
                                $msg = "Status changed to <strong>$status</strong> on $date.";
                            }
                            echo "<li>- $msg</li>";
                        endforeach;
                    else:
                        echo "<li class='text-muted'>No updates yet.</li>";
                    endif;
                    ?>
                </ul>
                <hr class="my-4">

                <!-- Add New Update Section -->
                <div class="update-section">
                    <h6 class="mb-3 text-dark fw-bold">
                        <i class="fas fa-plus-circle text-primary"></i>Add Progress Update
                    </h6>
                    <p class="text-muted fs-6 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Updates will be automatically sent as reports to the user's dashboard and email notifications.
                    </p>

                    <form id="phaseUpdateForm" enctype="multipart/form-data">
                        <input type="hidden" name="phase_name" value="Final Inspection">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">

                        <!-- Progress Description -->
                        <div class="mb-3">
                            <label for="updateDescription" class="form-label fw-semibold">
                                <i class="fas fa-edit me-1"></i>Progress Description
                            </label>
                            <textarea class="form-control" id="updateDescription" name="description"
                                rows="4" placeholder="Describe the current progress, challenges, or milestones achieved..." required></textarea>
                            <div class="form-text fs-6">
                                <i class="fas fa-lightbulb me-1"></i>
                                Be specific about what's been accomplished and any next steps.
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="mb-3">
                            <label for="progressImages" class="form-label fw-semibold">
                                <i class="fas fa-camera me-1"></i>Progress Photos & Documents
                            </label>
                            <input type="file" class="form-control" id="progressImages" name="progress_files[]"
                                multiple accept="image/*,.pdf,.doc,.docx,.dwg">
                            <div class="form-text">
                                <i class="fas fa-upload me-1"></i>
                                Upload photos, plans, permits, or documents
                            </div>
                            <div id="filePreview" class="mt-2"></div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Update & Notify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Final Inspection Phase Log Modal -->

<!-- Custom Styles -->
<style>
    .modal-dialog {
        max-width: 600px;
        margin: 1.75rem auto;
    }

    .timeline-item {
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .timeline-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .update-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #dee2e6;
    }

    .form-label {
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    #filePreview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .file-preview-item {
        position: relative;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        background: #f8f9fa;
        max-width: 150px;
    }

    .file-preview-item img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
    }

    .remove-file {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        cursor: pointer;
    }

    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
</style>

<!-- Progress Update for handling form submission -->
<script>
document.querySelectorAll('form[id^="phaseUpdateForm"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../forms_logic/save_phase_update.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                // Close the current modal first
                const currentModal = bootstrap.Modal.getInstance(this.closest('.modal'));
                if (currentModal) {
                    currentModal.hide();
                }
                
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                const modalElement = document.getElementById('successModal');
                
                let reloaded = false;
                
                // Function to handle reload (prevents multiple reloads)
                const handleReload = () => {
                    if (!reloaded) {
                        reloaded = true;
                        location.reload();
                    }
                };
                
                // Set up modal close event listener
                modalElement.addEventListener('hidden.bs.modal', handleReload, { once: true });
                
                // Fallback timeout in case modal event doesn't fire
                setTimeout(handleReload, 5000); // 5 second fallback
                
                successModal.show();
            })
            .catch(err => {
                console.error('Error submitting update:', err);
                alert('Error submitting update. Please try again.');
            });
    });
});
</script>
